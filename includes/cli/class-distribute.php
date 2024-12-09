<?php
/**
 * TODO.
 *
 * @package Newspack
 */

namespace Newspack_Network;

use Newspack_Network\Hub\Node as Hub_Node;
use WP_CLI;

class Distribute {
	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'init', [ __CLASS__, 'register_commands' ] );
		}
	}

	/**
	 * Register the WP-CLI commands
	 *
	 * @return void
	 */
	public static function register_commands() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// wp newspack network distribute {post_id} —sites(all|site_ids|site_urls)
			WP_CLI::add_command(
				'newspack-network distribute post',
				[ __CLASS__, 'cmd_distribute_post' ],
				[
					'shortdesc' => __( 'Distribute a post to all the network or the specified sites' ),
					'synopsis'  => [
						[
							'type'        => 'positional',
							'name'        => 'post-id',
							'description' => sprintf( __( 'The ID of the post to distribute. Supported post types are: %s' ),
								implode( ', ', Content_Distribution::get_distributed_post_types() ) ),
							'optional'    => false,
							'repeating'   => false,
						],
						[
							'type'        => 'assoc',
							'name'        => 'sites',
							'description' => __( 'The ID of the post to distribute.' ),
							'optional'    => false,
						]
					],
				]
			);
		}
	}

	public function cmd_distribute_post( array $pos_args, array $assoc_args ): void {
		$post_id = $pos_args[0];
		if ( ! is_numeric( $post_id ) ) {
			WP_CLI::error( 'Invalid post ID.' );
		}
		$supported_post_types = Content_Distribution::get_distributed_post_types();
		$post_type            = get_post_type( $post_id );
		if ( ! in_array( $post_type, $supported_post_types ) ) {
			WP_CLI::error( sprintf( __( "Post type '%s' is not supported. Only %s type(s) can be distributed" ), $post_type, implode( ', ', $supported_post_types ) ) );
		}

		$networked_urls = array_reduce(
			get_option( Hub_Node::HUB_NODES_SYNCED_OPTION, [] ),
			function ( $carry, $node ) {
				$carry[] = untrailingslashit( $node['url'] );

				return $carry;
			},
			[ untrailingslashit( Node\Settings::get_hub_url() ) ]
		);

		if ( 'all' === $assoc_args['sites'] ) {
			$sites = $networked_urls;
		} else {
			$sites        = explode( ',', ( $assoc_args['sites'] ) );
			$sites        = array_map( 'trim', $sites );
			$sites        = array_map( 'untrailingslashit', $sites );
			$intersection = array_intersect( $networked_urls, $sites );

			if ( count( $intersection ) !== count( $sites ) ) {
				WP_CLI::error( sprintf( 'Non-networked URL(s) passed: %s', implode( ', ', $sites ) ) );
			}
		}


		Content_Distribution::set_post_distribution( $post_id, $sites );
		Content_Distribution::distribute_post( $post_id );
		// TODO. Error checking
		echo sprintf( 'Distributed post %d to sites: %s', $post_id, implode( ', ', $sites ) );
	}
}