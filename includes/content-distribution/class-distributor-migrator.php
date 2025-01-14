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

/**
 * Distributor Migrator Class.
 */
class Distributor_Migrator {
	/**
	 * Get all Distributor subscriptions.
	 *
	 * @return WP_Post[] Array of WP_Post objects representing Distributor subscriptions.
	 */
	public static function get_distributor_subscriptions() {
		$query = new WP_Query(
			[
				'post_type'      => 'dt_subscription',
				'posts_per_page' => '-1',
			]
		);
		return $query->posts;
	}

	/**
	 * Migrate a post subscription from Distributor to Newspack Network Content Distribution.
	 *
	 * @param int     $subscription_id The ID of the subscription to migrate.
	 * @param boolean $distribute      Whether to distribute the post after migrating the subscription.
	 *
	 * @return Outgoing_Post|WP_Error Outgoing_Post on success, WP_Error on failure.
	 */
	public static function migrate_subscription( $subscription_id, $distribute = false ) {
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

		// Configure distribution.
		try {
			$outgoing_post = new Outgoing_Post( $post );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error( 'outgoing_post_error', $e->getMessage() );
		}
		$distribution = $outgoing_post->set_distribution( [ $network_url ] );
		if ( is_wp_error( $distribution ) ) {
			return $distribution;
		}

		if ( $distribute ) {
			Content_Distribution::distribute_post( $outgoing_post );
		}

		// Delete the post meta.
		delete_post_meta( $post_id, 'dt_subscriptions' );
		delete_post_meta( $post_id, 'dt_connection_map' );

		// Delete the subscription.
		wp_delete_post( $subscription_id );

		return $outgoing_post;
	}
}
