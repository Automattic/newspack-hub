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
				'shortdesc' => __( 'Migrate a post from Distributor to Newspack Network content distribution', 'newspack-network' ),
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'post-id',
						'description' => __( 'The ID of the post to migrate.', 'newspack-network' ),
						'repeating'   => false,
						'optional'    => true,
					],
					[
						'type'        => 'flag',
						'name'        => 'all',
						'description' => __( 'Migrate all posts.', 'newspack-network' ),
						'optional'    => true,
					],
					[
						'type'        => 'flag',
						'name'        => 'delete',
						'description' => __( 'Whether to deactivate and delete the Distributor plugin after migrating all subscriptions.', 'newspack-network' ),
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
		$post_id = $pos_args[0] ?? null;
		if ( ! is_numeric( $post_id ) && ! isset( $assoc_args['all'] ) ) {
			WP_CLI::error( 'Post ID must be a number.' );
		}

		if ( ! isset( $assoc_args['all'] ) && isset( $assoc_args['delete'] ) ) {
			WP_CLI::error( 'The --delete flag can only be used with the --all flag.' );
		}

		if ( isset( $assoc_args['all'] ) ) {
			$subscriptions = Distributor_Migrator::get_distributor_subscriptions();
			WP_CLI::line( sprintf( 'Found %d subscriptions.', count( $subscriptions ) ) );

			// Validate whether all subscriptions can be migrated.
			$errors = [];
			foreach ( $subscriptions as $subscription_id ) {
				$can_migrate = Distributor_Migrator::can_migrate_subscription( $subscription_id );
				if ( is_wp_error( $can_migrate ) ) {
					$errors[] = sprintf( 'Unable to migrate subscription %d: %s', $subscription_id, $can_migrate->get_error_message() );
				}
			}
			if ( ! empty( $errors ) ) {
				WP_CLI::error( PHP_EOL . implode( PHP_EOL, $errors ) );
			}

			// Migrate subscriptions.
			foreach ( $subscriptions as $i => $subscription_id ) {
				$result = Distributor_Migrator::migrate_subscription( $subscription_id );
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( $result->get_error_message() );
				}
				WP_CLI::line( sprintf( '(%d/%d) Subscription with ID %d is migrated.', $i + 1, count( $subscriptions ), $subscription_id ) );
			}

			if ( isset( $assoc_args['delete'] ) ) {
				deactivate_plugins( [ 'distributor/distributor.php' ] );
				delete_plugins( [ 'distributor/distributor.php' ] );
				WP_CLI::line( 'Distributor plugin is deactivated and deleted.' );
			}
		} else {
			$result = Distributor_Migrator::migrate_post( $post_id );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
		}

		WP_CLI::success( 'Migration completed.' );
	}
}
