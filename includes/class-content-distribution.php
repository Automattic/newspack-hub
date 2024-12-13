<?php
/**
 * Newspack Network Content Distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network;

use Newspack\Data_Events;
use Newspack_Network\Content_Distribution\CLI;
use Newspack_Network\Content_Distribution\Editor;
use Newspack_Network\Content_Distribution\Incoming_Post;
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
		if ( ! defined( 'NEWPACK_NETWORK_CONTENT_DISTRIBUTION' ) || ! NEWPACK_NETWORK_CONTENT_DISTRIBUTION ) {
			return;
		}
		CLI::init();
		add_action( 'init', [ __CLASS__, 'register_listeners' ] );
		add_filter( 'newspack_webhooks_request_priority', [ __CLASS__, 'webhooks_request_priority' ], 10, 2 );
		add_action( 'updated_postmeta', [ __CLASS__, 'handle_postmeta_update' ], 10, 3 );

		Editor::init();
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
	 * Distribute post on postmeta update.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 */
	public static function handle_postmeta_update( $meta_id, $object_id, $meta_key ) {
		if ( ! $object_id ) {
			return;
		}
		$post = get_post( $object_id );
		if ( ! $post ) {
			return;
		}
		if ( ! in_array( $post->post_type, self::get_distributed_post_types(), true ) ) {
			return;
		}
		// Ignore reserved keys but run if the meta is setting the distribution.
		if (
			Outgoing_Post::DISTRIBUTED_POST_META !== $meta_key &&
			in_array( $meta_key, self::get_reserved_post_meta_keys(), true )
		) {
			return;
		}
		self::distribute_post( $post );
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
	 * Get post meta keys that should be ignored on content distribution.
	 *
	 * @return string[] The reserved post meta keys.
	 */
	public static function get_reserved_post_meta_keys() {
		$reserved_keys = [
			'_edit_lock',
			'_edit_last',
			'_thumbnail_id',
			'_yoast_wpseo_primary_category',
		];

		/**
		 * Filters the reserved post meta keys that should not be distributed.
		 *
		 * @param string[] $reserved_keys The reserved post meta keys.
		 * @param WP_Post  $post          The post object.
		 */
		$reserved_keys = apply_filters( 'newspack_network_content_distribution_reserved_post_meta_keys', $reserved_keys );

		// Always preserve content distribution post meta.
		return array_merge(
			$reserved_keys,
			[
				Outgoing_Post::DISTRIBUTED_POST_META,
				Incoming_Post::NETWORK_POST_ID_META,
				Incoming_Post::PAYLOAD_META,
				Incoming_Post::UNLINKED_META,
				Incoming_Post::ATTACHMENT_META,
			]
		);
	}

	/**
	 * Get a distributed post.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return Outgoing_Post|null The distributed post or null if not found, or we couldn't create one.
	 */
	public static function get_distributed_post( $post ) {
		try {
			$outgoing_post = new Outgoing_Post( $post );
		} catch ( \InvalidArgumentException ) {
			return null;
		}

		return $outgoing_post->is_distributed() ? $outgoing_post : null;
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
