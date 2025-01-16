<?php
/**
 * Newspack Network Content Distribution Distributor Migrator.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use Newspack_Network\Utils\Network;
use WP_Error;
use WP_Query;
use WP_Post;
use InvalidArgumentException;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Distributor Migrator Class.
 */
class Distributor_Migrator {
	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'newspack-network/v1',
			'/content-distribution/distributor-migrator/link/(?P<post_id>\d+)',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'api_link_incoming_post' ],
				'args'                => [
					'subscription_signature' => [
						'type'     => 'string',
						'required' => true,
					],
					'payload'                => [
						'type'     => 'object',
						'required' => true,
					],
				],
				'permission_callback' => '__return_true', // TODO: Check network signature.
			]
		);
	}

	/**
	 * API callback to link a migrated incoming post.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST response or error.
	 */
	public static function api_link_incoming_post( $request ) {
		$post_id                = $request->get_param( 'post_id' );
		$subscription_signature = $request->get_param( 'subscription_signature' );
		$payload                = $request->get_param( 'payload' );

		$response = self::link_incoming_post( $post_id, $subscription_signature, $payload );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( null, 200 );
	}

	/**
	 * Link an incoming post given its subscription signature.
	 *
	 * @param int    $post_id                The ID of the post to link.
	 * @param string $subscription_signature The signature of the subscription.
	 * @param string $payload                The incoming post payload.
	 *
	 * @return WP_Error|void WP_Error on failure, void on success.
	 */
	protected static function link_incoming_post( $post_id, $subscription_signature, $payload ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'newspack-network' ) );
		}

		$post_signature = get_post_meta( $post_id, 'dt_subscription_signature', true );
		if ( $post_signature !== $subscription_signature ) {
			return new WP_Error( 'subscription_signature_mismatch', __( 'Subscription signature mismatch.', 'newspack-network' ) );
		}

		// Validate payload.
		$payload_error = Incoming_Post::get_payload_error( $payload );
		if ( is_wp_error( $payload_error ) ) {
			return new WP_Error(
				'payload_error',
				sprintf(
					// translators: payload error message.
					__( 'Payload error: %s', 'newspack-network' ),
					$payload_error->get_error_message()
				)
			);
		}

		// Store payload for insertion.
		update_post_meta( $post_id, Incoming_Post::PAYLOAD_META, $payload );

		try {
			$incoming_post = new Incoming_Post( $post_id );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error( 'incoming_post_error', $e->getMessage() );
		}

		// Match the unlinked state.
		if ( get_post_meta( $post_id, 'dt_unlinked', true ) ) {
			$incoming_post->set_unlinked();
		}

		// Insert the incoming post.
		$insert = $incoming_post->insert();
		if ( is_wp_error( $insert ) ) {
			return $insert;
		}

		// Clear Distributor meta.
		$distributor_meta = [
			'dt_full_connection',
			'dt_original_post_id',
			'dt_original_post_url',
			'dt_original_site_name',
			'dt_original_site_url',
			'dt_original_source_id',
			'dt_subscription_signature',
			'dt_syndicate_time',
			'dt_unlinked',
			'dt_subscriptions',
			'dt_connection_map',
		];
		foreach ( $distributor_meta as $meta_key ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	/**
	 * Get all Distributor subscriptions.
	 *
	 * @return int[] Array of Distributor subscription IDs.
	 */
	public static function get_distributor_subscriptions() {
		return get_posts(
			[
				'post_type'      => 'dt_subscription',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
	}

	/**
	 * Migrate a post from Distributor to Newspack Network Content Distribution.
	 *
	 * @param int $post_id The ID of the post to migrate.
	 *
	 * @return Outgoing_Post|WP_Error Outgoing_Post on success, WP_Error on failure.
	 */
	public static function migrate_post( $post_id ) {
		$subscriptions = get_post_meta( $post_id, 'dt_subscriptions', true );
		if ( ! $subscriptions ) {
			return new WP_Error( 'subscriptions_not_found', __( 'Subscriptions not found.', 'newspack-network' ) );
		}

		// First check whether all subscriptions can be migrated.
		foreach ( $subscriptions as $subscription_id ) {
			$can_migrate = self::can_migrate_subscription( $subscription_id );
			if ( is_wp_error( $can_migrate ) ) {
				return $can_migrate;
			}
		}

		// Migrate subscriptions.
		$outgoing_post = null;
		foreach ( $subscriptions as $subscription_id ) {
			$migration_result = self::migrate_subscription( $subscription_id );
			if ( is_wp_error( $migration_result ) ) {
				return $migration_result;
			}
			$outgoing_post = $migration_result;
		}

		return $outgoing_post;
	}

	/**
	 * Get network URL from a subscription.
	 *
	 * @param int $subscription_id The ID of the subscription.
	 *
	 * @return string|WP_Error The network URL on success, WP_Error on failure.
	 */
	protected static function get_network_url_from_subscription( $subscription_id ) {
		$target_url = get_post_meta( $subscription_id, 'dt_subscription_target_url', true );
		if ( ! $target_url ) {
			return new WP_Error( 'target_url_not_found', __( 'Target URL not found.', 'newspack-network' ) );
		}

		$network_urls = Network::get_networked_urls();
		$network_url  = array_filter(
			$network_urls,
			function( $url ) use ( $target_url ) {
				return false !== strpos( $target_url, $url );
			}
		);
		$network_url  = array_shift( $network_url );
		if ( empty( $network_url ) ) {
			return new WP_Error(
				'target_url_not_networked',
				sprintf(
					// translators: target URL.
					__( 'Target URL "%s" is not networked.', 'newspack-network' ),
					$target_url
				)
			);
		}
		return $network_url;
	}

	/**
	 * Validate whether a subscription can be migrated.
	 *
	 * @param int $subscription_id The ID of the subscription to check.
	 *
	 * @return true|WP_Error True if the subscription can be migrated, WP_Error on failure.
	 */
	public static function can_migrate_subscription( $subscription_id ) {
		$subscription = get_post( $subscription_id );
		if ( ! $subscription ) {
			return new WP_Error( 'subscription_not_found', __( 'Subscription not found.', 'newspack-network' ) );
		}

		$post_id = get_post_meta( $subscription_id, 'dt_subscription_post_id', true );
		$post    = get_post( $post_id );
		if ( ! $post_id || ! $post || empty( $post->ID ) ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'newspack-network' ) );
		}

		$target_url = get_post_meta( $subscription_id, 'dt_subscription_target_url', true );
		if ( ! $target_url ) {
			return new WP_Error( 'target_url_not_found', __( 'Target URL not found.', 'newspack-network' ) );
		}

		$network_url = self::get_network_url_from_subscription( $subscription_id );
		if ( is_wp_error( $network_url ) ) {
			return $network_url;
		}

		return true;
	}

	/**
	 * Migrate a post subscription from Distributor to Newspack Network Content Distribution.
	 *
	 * @param int $subscription_id The ID of the subscription to migrate.
	 *
	 * @return Outgoing_Post|WP_Error Outgoing_Post on success, WP_Error on failure.
	 */
	public static function migrate_subscription( $subscription_id ) {
		$can_migrate = self::can_migrate_subscription( $subscription_id );
		if ( is_wp_error( $can_migrate ) ) {
			return $can_migrate;
		}

		$post_id     = get_post_meta( $subscription_id, 'dt_subscription_post_id', true );
		$network_url = self::get_network_url_from_subscription( $subscription_id );

		// Configure distribution.
		try {
			$outgoing_post = new Outgoing_Post( $post_id );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error( 'outgoing_post_error', $e->getMessage() );
		}
		$distribution = $outgoing_post->set_distribution( [ $network_url ] );
		if (
			is_wp_error( $distribution ) &&
			// Ignore error if the post is already distributed.
			'update_failed' !== $distribution->get_error_code()
		) {
			return $distribution;
		}

		// Link the migrated post.
		$link_result = self::link_migrated_subscription( $network_url, $subscription_id, $outgoing_post->get_payload() );
		if ( is_wp_error( $link_result ) ) {
			return $link_result;
		}

		// Clear the subscription meta from the post.
		$subscriptions = get_post_meta( $post_id, 'dt_subscriptions', true );
		$subscriptions = array_diff( $subscriptions, [ $subscription_id ] );
		if ( empty( $subscriptions ) ) {
			delete_post_meta( $post_id, 'dt_subscriptions' );
		} else {
			update_post_meta( $post_id, 'dt_subscriptions', $subscriptions );
		}

		// Clear the connection map from the post.
		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );
		$remote_post_id = get_post_meta( $subscription_id, 'dt_subscription_remote_post_id', true );
		if ( ! empty( $connection_map['external'] ) ) {
			foreach ( $connection_map['external'] as $connection_id => $value ) {
				if ( absint( $value['post_id'] ) === absint( $remote_post_id ) ) {
					unset( $connection_map['external'][ $connection_id ] );
				}
			}
		}
		if ( empty( $connection_map['external'] ) && empty( $connection_map['internal'] ) ) {
			delete_post_meta( $post_id, 'dt_connection_map' );
		} else {
			update_post_meta( $post_id, 'dt_connection_map', $connection_map );
		}

		// Delete the subscription post.
		wp_delete_post( $subscription_id );

		return $outgoing_post;
	}

	/**
	 * Trigger the request to link the incoming post from a subscription being
	 * migrated.
	 *
	 * @param string $site_url        The URL of the destination site.
	 * @param int    $subscription_id The ID of the subscription being migrated.
	 * @param string $payload         The outgoing post payload.
	 *
	 * @return WP_Error|void WP_Error on failure, void on success.
	 */
	protected static function link_migrated_subscription( $site_url, $subscription_id, $payload ) {
		$remote_post_id = get_post_meta( $subscription_id, 'dt_subscription_remote_post_id', true );
		$signature      = get_post_meta( $subscription_id, 'dt_subscription_signature', true );
		$url            = trailingslashit( $site_url ) . 'wp-json/newspack-network/v1/content-distribution/distributor-migrator/link/' . $remote_post_id;
		$response       = wp_remote_post(
			$url,
			[
				'body'    => [
					'subscription_signature' => $signature,
					'payload'                => $payload,
				],
				'timeout' => 20, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			]
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$link_response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $link_response_code ) {
			$link_response_body = wp_remote_retrieve_body( $response );
			$error_message      = __( 'Error linking migrated post.', 'newspack-network' );
			if ( $link_response_body ) {
				$error_message .= ' ' . $link_response_body;
			}
			return new WP_Error( 'link_migrated_subscription_error', $error_message );
		}
	}
}
