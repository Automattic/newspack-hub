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
	 * Get all Distributor subscriptions.
	 *
	 * @return int[] Array of Distributor subscription IDs.
	 */
	protected static function get_distributor_subscriptions() {
		return get_posts(
			[
				'post_type'      => 'dt_subscription',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
	}

	/**
	 * Get posts with Distributor subscriptions.
	 *
	 * @return int[] Array of post IDs.
	 */
	public static function get_outgoing_posts() {
		$subscriptions = self::get_distributor_subscriptions();
		$posts         = [];
		foreach ( $subscriptions as $subscription_id ) {
			$post_id = get_post_meta( $subscription_id, 'dt_subscription_post_id', true );
			if ( ! $post_id ) {
				continue;
			}
			$posts[ $post_id ] = $post_id;
		}
		return array_values( $posts );
	}

	/**
	 * Get all Distributor linked posts.
	 */
	public static function get_incoming_posts() {
		$posts = new WP_Query(
			[
				'post_type'      => 'any',
				'posts_per_page' => -1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => 'dt_full_connection',
						'compare' => 'EXISTS',
					],
				],
				'fields'         => 'ids',
			]
		);
		return $posts->posts;
	}

	/**
	 * Migrate a post from Distributor to Newspack Network Content Distribution.
	 *
	 * @param int $post_id The ID of the post to migrate.
	 *
	 * @return Outgoing_Post|WP_Error Outgoing_Post on success, WP_Error on failure.
	 */
	protected static function migrate_outgoing_post( $post_id ) {
		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );
		if ( ! $connection_map || empty( $connection_map['external'] ) ) {
			return new WP_Error( 'no_connection_map', __( 'No connections found.', 'newspack-network' ) );
		}

		if ( ! empty( $connection_map['internal'] ) ) {
			return new WP_Error( 'internal_connection', __( 'This post contains internal connections, which are not supported.', 'newspack-network' ) );
		}

		$subscriptions = get_post_meta( $post_id, 'dt_subscriptions', true );
		if ( ! $subscriptions || ! is_array( $subscriptions ) ) {
			return new WP_Error( 'subscriptions_not_found', __( 'Subscriptions not found.', 'newspack-network' ) );
		}

		foreach ( $subscriptions as $subscription_id ) {
			$can_migrate_subscription = self::can_migrate_subscription( $subscription_id );
			if ( is_wp_error( $can_migrate_subscription ) ) {
				return $can_migrate_subscription;
			}
		}

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
	 * Get a network site URL from a given URL.
	 *
	 * @param string $url The URL to match.
	 *
	 * @return string|false The network site URL, or false if not found.
	 */
	protected static function get_network_url( $url ) {
		$network_urls = Network::get_networked_urls();
		$network_url  = array_filter(
			$network_urls,
			function( $url ) use ( $original_site_url ) {
				return false !== strpos( $original_site_url, $url );
			}
		);
		$network_url  = array_shift( $network_url );
		return $network_url ? $network_url : false;
	}


	/**
	 * Migrate an incoming post.
	 *
	 * @param int $post_id The ID of the post to link.
	 *
	 * @return WP_Error|void WP_Error on failure, void on success.
	 */
	protected static function migrate_incoming_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'newspack-network' ) );
		}

		$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );
		if ( ! $original_post_id ) {
			return new WP_Error( 'original_post_id_not_found', __( 'Original post ID not found.', 'newspack-network' ) );
		}

		$original_site_url = get_post_meta( $post_id, 'dt_original_site_url', true );
		if ( ! $original_site_url ) {
			return new WP_Error( 'original_site_url_not_found', __( 'Original site URL not found.', 'newspack-network' ) );
		}

		$network_url = self::get_network_url( $original_site_url );
		if ( empty( $network_url ) ) {
			return new WP_Error(
				'site_url_not_networked',
				sprintf(
					// translators: target URL.
					__( 'Site URL "%s" is not networked.', 'newspack-network' ),
					$original_site_url
				)
			);
		}

		// Instantiate an Outgoing_Post to configure its origin.
		$outgoing_post = new Outgoing_Post( $post_id );
		$payload       = $outgoing_post->get_payload();

		// Modify payload to match the origin.
		$payload['site_url']        = $network_url;
		$payload['post_id']         = $original_post_id;
		$payload['post_url']        = get_post_meta( $post_id, 'dt_original_post_url', true );
		$payload['sites']           = [ get_bloginfo( 'url' ) ]; // This can contain other sites, but we just care about the current site at this moment.
		$payload['network_post_id'] = md5( $network_url . $original_post_id );

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
	 * Validate whether a subscription can be migrated.
	 *
	 * @param int $subscription_id The ID of the subscription to check.
	 *
	 * @return true|WP_Error True if the subscription can be migrated, WP_Error on failure.
	 */
	protected static function can_migrate_subscription( $subscription_id ) {
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
	protected static function migrate_subscription( $subscription_id ) {
		$can_migrate = self::can_migrate_subscription( $subscription_id );
		if ( is_wp_error( $can_migrate ) ) {
			return $can_migrate;
		}

		$post_id = get_post_meta( $subscription_id, 'dt_subscription_post_id', true );

		$target_url = get_post_meta( $subscription_id, 'dt_subscription_target_url', true );
		if ( ! $target_url ) {
			return new WP_Error( 'target_url_not_found', __( 'Target URL not found.', 'newspack-network' ) );
		}
		$network_url = self::get_network_url( $target_url );
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
	 * Migrate a post.
	 *
	 * @param int $post_id The ID of the post to migrate.
	 *
	 * @return WP_Error|void WP_Error on failure, void on success.
	 */
	public static function migrate_post( $post_id ) {
		if ( get_post_meta( $post_id, 'dt_full_connection', true ) ) {
			$migrate_incoming_post = self::migrate_incoming_post( $post_id );
			if ( is_wp_error( $migrate_incoming_post ) ) {
				return $migrate_incoming_post;
			}
		} else {
			$migrate_outgoing_post = self::migrate_outgoing_post( $post_id );
			if ( is_wp_error( $migrate_outgoing_post ) ) {
				return $migrate_outgoing_post;
			}
		}
	}
}
