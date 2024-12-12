<?php
/**
 * Newspack Network Content Distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network;

use Newspack\Data_Events;
use Newspack_Network\Content_Distribution\CLI;
use Newspack_Network\Content_Distribution\Outgoing_Post;
use WP_Post;

/**
 * Main class for content distribution
 */
class Content_Distribution {
	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_listeners' ] );
		add_filter( 'newspack_webhooks_request_priority', [ __CLASS__, 'webhooks_request_priority' ], 10, 2 );
		CLI::init();
	}

	/**
	 * Register the listeners to the Newspack Data Events API
	 *
	 * @return void
	 */
	public static function register_listeners() {
		if ( ! class_exists( 'Newspack\Data_Events' ) ) {
			return;
		}
		Data_Events::register_listener( 'wp_after_insert_post', 'network_post_updated', [ __CLASS__, 'handle_post_updated' ] );
		Data_Events::register_listener( 'newspack_network_incoming_post_inserted', 'network_incoming_post_inserted', [ __CLASS__, 'handle_incoming_post_inserted' ] );
	}

	/**
	 * Filter the webhooks request priority so `network_post_updated` is
	 * prioritized.
	 *
	 * @param int    $priority    The request priority.
	 * @param string $action_name The action name.
	 *
	 * @return int The request priority.
	 */
	public static function webhooks_request_priority( $priority, $action_name ) {
		if ( 'network_post_updated' === $action_name ) {
			return 1;
		}
		return $priority;
	}

	/**
	 * Post update listener callback.
	 *
	 * @param Outgoing_Post|WP_Post|int $post The post object or ID.
	 *
	 * @return array|null The post payload or null if the post is not distributed.
	 */
	public static function handle_post_updated( $post ) {
		if ( ! $post instanceof Outgoing_Post ) {
			$post = self::get_distributed_post( $post );
		}

		if ( $post ) {
			return $post->get_payload();
		}

		return null;
	}

	/**
	 * Incoming post inserted listener callback.
	 *
	 * @param int     $post_id      The post ID.
	 * @param boolean $is_linked    Whether the post is unlinked.
	 * @param array   $post_payload The post payload.
	 */
	public static function handle_incoming_post_inserted( $post_id, $is_linked, $post_payload ) {
		return [
			'origin'      => [
				'site_url' => $post_payload['site_url'],
				'post_id'  => $post_payload['post_id'],
			],
			'destination' => [
				'site_url'  => get_bloginfo( 'url' ),
				'post_id'   => $post_id,
				'is_linked' => $is_linked,
			],
		];
	}

	/**
	 * Get the post types that are allowed to be distributed across the network.
	 *
	 * @return array Array of post types.
	 */
	public static function get_distributed_post_types() {
		/**
		 * Filters the post types that are allowed to be distributed across the network.
		 *
		 * @param array $post_types Array of post types.
		 */
		return apply_filters( 'newspack_network_distributed_post_types', [ 'post' ] );
	}

	/**
	 * Get a distributed post.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return Outgoing_Post|null The distributed post or null if not found.
	 */
	public static function get_distributed_post( $post ) {
		try {
			$outgoing_post = new Outgoing_Post( $post );
			if ( $outgoing_post->is_distributed() ) {
				return $outgoing_post;
			}
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Manually trigger post distribution.
	 *
	 * @param WP_Post|Outgoing_Post|int $post The post object or ID.
	 *
	 * @return void
	 */
	public static function distribute_post( $post ) {
		$data = self::handle_post_updated( $post );
		if ( $data ) {
			Data_Events::dispatch( 'network_post_updated', $data );
		}
	}
}
