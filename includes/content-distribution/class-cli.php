<?php
/**
 * Network Content Distribution commands.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use Newspack_Network\Utils\Network;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * Class Distribution.
 */
class CLI {
	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'init', [ __CLASS__, 'register_commands' ] );
		}
	}

	/**
	 * Callback to register the WP-CLI commands.
	 *
	 * @return void
	 * @throws \Exception If something goes wrong.
	 */
	public static function register_commands(): void {
		WP_CLI::add_command(
			'newspack network distribute post',
			[ __CLASS__, 'cmd_distribute_post' ],
			[
				'shortdesc' => __( 'Distribute a post to all the network or the specified sites', 'newspack-network' ),
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'post-id',
						'description' => sprintf(
							// translators: %s: list of supported post types.
							__( 'The ID of the post to distribute. Supported post types are: %s', 'newspack-network' ),
							implode(
								', ',
								Content_Distribution::get_distributed_post_types()
							)
						),
						'optional'    => false,
						'repeating'   => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'sites',
						'description' => __( "Networked site url(s) comma separated to distribute the post to – or 'all' to distribute to all sites in the network.", 'newspack-network' ),
						'optional'    => false,
					],
				],
			]
		);
		WP_CLI::add_command(
			'newspack network distributor migrate',
			[ __CLASS__, 'cmd_distributor_migrate' ],
			[
				'shortdesc' => __( 'Migrate posts from Distributor to Newspack Network\'s content distribution', 'newspack-network' ),
				'synopsis'  => [
					[
						'type'        => 'flag',
						'name'        => 'delete',
						'description' => __( 'Whether to deactivate and delete the Distributor plugin after migrating all posts. This will only take effect if all posts were able to migrate.', 'newspack-network' ),
						'optional'    => true,
					],
				],
			]
		);
	}

	/**
	 * Callback for the `newspack-network distribute post` command.
	 *
	 * @param array $pos_args   Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @throws ExitException If something goes wrong.
	 */
	public function cmd_distribute_post( array $pos_args, array $assoc_args ): void {
		$post_id = $pos_args[0];
		if ( ! is_numeric( $post_id ) ) {
			WP_CLI::error( 'Post ID must be a number.' );
		}

		if ( 'all' === $assoc_args['sites'] ) {
			$sites = Network::get_networked_urls();
		} else {
			$sites = array_map(
				fn( $site ) => untrailingslashit( trim( $site ) ),
				explode( ',', $assoc_args['sites'] )
			);
		}

		try {
			$outgoing_post = Content_Distribution::get_distributed_post( $post_id ) ?? new Outgoing_Post( $post_id );
			$sites = $outgoing_post->set_distribution( $sites );
			if ( is_wp_error( $sites ) ) {
				WP_CLI::error( $sites->get_error_message() );
			}

			Content_Distribution::distribute_post( $outgoing_post );
			WP_CLI::success( sprintf( 'Post with ID %d is distributed to %d sites: %s', $post_id, count( $sites ), implode( ', ', $sites ) ) );

		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Callback for the `newspack-network distributor migrate` command.
	 *
	 * @param array $pos_args   Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @throws ExitException If something goes wrong.
	 */
	public function cmd_distributor_migrate( array $pos_args, array $assoc_args ): void {
		$outgoing_post_ids = Distributor_Migrator::get_outgoing_posts();
		$incoming_post_ids = Distributor_Migrator::get_incoming_posts();

		if ( empty( $outgoing_post_ids ) && empty( $incoming_post_ids ) ) {
			WP_CLI::success( 'No posts found to migrate.' );
			return;
		}

		WP_CLI::line( empty( $outgoing_post_ids ) ? 'No outgoing posts found.' : sprintf( 'Found %d outgoing posts.', count( $outgoing_post_ids ) ) );
		WP_CLI::line( empty( $incoming_post_ids ) ? 'No incoming posts found.' : sprintf( 'Found %d incoming posts.', count( $incoming_post_ids ) ) );

		$post_ids = array_merge( $outgoing_post_ids, $incoming_post_ids );

		$errored = false;
		foreach ( $post_ids as $i => $post_id ) {
			$result = Distributor_Migrator::migrate_post( $post_id );
			if ( is_wp_error( $result ) ) {
				$errored = true;
				WP_CLI::line( sprintf( '(%d/%d) Post %d could not be migrated: %s', $i + 1, count( $post_ids ), $post_id, $result->get_error_message() ) );
			} else {
				WP_CLI::line( sprintf( '(%d/%d) Post %d is migrated.', $i + 1, count( $post_ids ), $post_id ) );
			}
		}

		if ( isset( $assoc_args['delete'] ) && ! $errored ) {
			deactivate_plugins( [ 'distributor/distributor.php' ] );
			delete_plugins( [ 'distributor/distributor.php' ] );
			WP_CLI::line( 'Distributor plugin is deactivated and deleted.' );
		}

		WP_CLI::success( 'Migration completed.' );
	}
}
