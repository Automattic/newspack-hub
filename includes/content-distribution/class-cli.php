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
				'shortdesc' => __( 'Distribute a post to all the network or the specified sites' ),
				'synopsis'  => [
					[
						'type'        => 'positional',
						'name'        => 'post-id',
						'description' => sprintf(
							'The ID of the post to distribute. Supported post types are: %s',
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
						'description' => __( "Networked site url(s) comma separated to distribute the post to – or 'all' to distribute to all sites in the network." ),
						'optional'    => false,
					],
					[
						'type'        => 'assoc',
						'name'        => 'status_on_create',
						'description' => __( 'The post status when creating the post. Default is to distribute as draft.' ),
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

			Content_Distribution::distribute_post( $outgoing_post, $assoc_args['status_on_create'] ?? 'draft' );
			WP_CLI::success( sprintf( 'Post with ID %d is distributed to %d sites: %s', $post_id, count( $sites ), implode( ', ', $sites ) ) );

		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}
}
