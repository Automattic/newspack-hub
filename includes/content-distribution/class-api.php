<?php
/**
 * Newspack Network Content Distribution API.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use WP_Error;

/**
 * API Class.
 */
class API {
	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register the REST API routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'newspack-network/v1',
			'/content-distribution/distribute/(?P<post_id>\d+)',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'distribute' ],
				'args'                => [
					'urls' => [
						'type'     => 'array',
						'required' => true,
						'items'    => [
							'type' => 'string',
						],
					],
				],
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' ); // @TODO Custom capability.
				},
			]
		);
	}

	/**
	 * Distribute a post to the network.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public static function distribute( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$urls    = $request->get_param( 'urls' );

		try {
			$outgoing_post = new Outgoing_Post( $post_id );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_Error( 'newspack_network_content_distribution_error', $e->getMessage(), [ 'status' => 400 ] );
		}

		$distribution = $outgoing_post->set_distribution( $urls );

		if ( is_wp_error( $distribution ) ) {
			return new WP_Error( 'newspack_network_content_distribution_error', $distribution->get_error_message(), [ 'status' => 400 ] );
		}

		Content_Distribution::distribute_post( $outgoing_post );

		return rest_ensure_response( $distribution );
	}
}
