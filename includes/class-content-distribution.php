<?php
/**
 * Newspack Network Content Distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network;

use Newspack\Data_Events;
use Newspack_Network\Content_Distribution\CLI;
use Newspack_Network\Content_Distribution\API;
use Newspack_Network\Content_Distribution\Editor;
use Newspack_Network\Content_Distribution\Incoming_Post;
use Newspack_Network\Content_Distribution\Outgoing_Post;
use WP_Post;
use WP_Error;

/**
 * Main class for content distribution
 */
class Content_Distribution {
	/**
	 * Queued network post updates.
	 *
	 * @var array Post IDs to update.
	 */
	private static $queued_post_updates = [];

	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		// Place content distribution behind a constant but run under unit tests.
		if (
			! ( defined( 'IS_TEST_ENV' ) && IS_TEST_ENV ) &&
			( ! defined( 'NEWPACK_NETWORK_CONTENT_DISTRIBUTION' ) || ! NEWPACK_NETWORK_CONTENT_DISTRIBUTION )
		) {
			return;
		}

		add_action( 'init', [ __CLASS__, 'register_data_event_actions' ] );
		add_action( 'shutdown', [ __CLASS__, 'distribute_queued_posts' ] );
		add_filter( 'newspack_webhooks_request_priority', [ __CLASS__, 'webhooks_request_priority' ], 10, 2 );
		add_filter( 'update_post_metadata', [ __CLASS__, 'maybe_short_circuit_distributed_meta' ], 10, 4 );
		add_action( 'wp_after_insert_post', [ __CLASS__, 'handle_post_updated' ] );
		add_action( 'updated_postmeta', [ __CLASS__, 'handle_postmeta_update' ], 10, 3 );
		add_action( 'newspack_network_incoming_post_inserted', [ __CLASS__, 'handle_incoming_post_inserted' ], 10, 3 );

		CLI::init();
		API::init();
		Editor::init();
	}

	/**
	 * Register the data event actions for content distribution.
	 *
	 * @return void
	 */
	public static function register_data_event_actions() {
		if ( ! class_exists( 'Newspack\Data_Events' ) ) {
			return;
		}
		Data_Events::register_action( 'network_post_updated' );
		Data_Events::register_action( 'network_incoming_post_inserted' );
	}

	/**
	 * Distribute queued posts.
	 */
	public static function distribute_queued_posts() {
		$post_ids = array_unique( self::$queued_post_updates );
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			self::distribute_post( $post );
		}
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
	 * Validate whether an update to DISTRIBUTED_POST_META is allowed.
	 *
	 * @param null|bool $check      Whether to allow updating metadata for the given type. Default null.
	 * @param int       $object_id  Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Metadata value.
	 */
	public static function maybe_short_circuit_distributed_meta( $check, $object_id, $meta_key, $meta_value ) {
		if ( Outgoing_Post::DISTRIBUTED_POST_META !== $meta_key ) {
			return $check;
		}

		// Ensure the post type can be distributed.
		$post_types = self::get_distributed_post_types();
		if ( ! in_array( get_post_type( $object_id ), $post_types, true ) ) {
			return false;
		}

		if ( is_wp_error( Outgoing_Post::validate_distribution( $meta_value ) ) ) {
			return false;
		}

		// Prevent removing existing distributions.
		$current_value = get_post_meta( $object_id, $meta_key, true );
		if ( ! empty( array_diff( empty( $current_value ) ? [] : $current_value, $meta_value ) ) ) {
			return false;
		}

		return $check;
	}

	/**
	 * Distribute post on postmeta update.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 *
	 * @return void
	 */
	public static function handle_postmeta_update( $meta_id, $object_id, $meta_key ) {
		if ( ! $object_id ) {
			return;
		}
		$post = get_post( $object_id );
		if ( ! $post ) {
			return;
		}
		if ( ! self::is_post_distributed( $post ) ) {
			return;
		}
		// Ignore reserved keys but run if the meta is setting the distribution.
		if (
			Outgoing_Post::DISTRIBUTED_POST_META !== $meta_key &&
			in_array( $meta_key, self::get_reserved_post_meta_keys(), true )
		) {
			return;
		}
		self::$queued_post_updates[] = $object_id;
	}

	/**
	 * Distribute post on post updated.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return void
	 */
	public static function handle_post_updated( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return;
		}
		if ( ! self::is_post_distributed( $post ) ) {
			return;
		}
		self::$queued_post_updates[] = $post->ID;
	}

	/**
	 * Incoming post inserted listener callback.
	 *
	 * @param int     $post_id      The post ID.
	 * @param boolean $is_linked    Whether the post is unlinked.
	 * @param array   $post_payload The post payload.
	 */
	public static function handle_incoming_post_inserted( $post_id, $is_linked, $post_payload ) {
		if ( ! class_exists( 'Newspack\Data_Events' ) ) {
			return;
		}
		$data = [
			'network_post_id' => $post_payload['network_post_id'],
			'outgoing'        => [
				'site_url' => $post_payload['site_url'],
				'post_id'  => $post_payload['post_id'],
				'post_url' => $post_payload['post_url'],
			],
			'incoming'        => [
				'site_url'  => get_bloginfo( 'url' ),
				'post_id'   => $post_id,
				'post_url'  => get_permalink( $post_id ),
				'is_linked' => $is_linked,
			],
		];
		Data_Events::dispatch( 'network_incoming_post_inserted', $data );
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
	 * Whether a given post is distributed.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return bool Whether the post is distributed.
	 */
	public static function is_post_distributed( $post ) {
		return (bool) self::get_distributed_post( $post );
	}

	/**
	 * Whether a given post is an incoming post. This will also return true if
	 * the post is unlinked.
	 *
	 * Since the Incoming_Post object queries the post by post meta on
	 * instantiation, this method is more efficient for checking if a post is
	 * incoming.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return bool Whether the post is an incoming post.
	 */
	public static function is_post_incoming( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}
		return (bool) get_post_meta( $post->ID, Incoming_Post::PAYLOAD_META, true );
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
	 * Trigger post distribution.
	 *
	 * @param WP_Post|Outgoing_Post|int $post The post object or ID.
	 *
	 * @return void
	 */
	public static function distribute_post( $post ) {
		if ( ! class_exists( 'Newspack\Data_Events' ) ) {
			return;
		}
		if ( $post instanceof Outgoing_Post ) {
			$distributed_post = $post;
		} else {
			$distributed_post = self::get_distributed_post( $post );
		}
		if ( $distributed_post ) {
			Data_Events::dispatch( 'network_post_updated', $distributed_post->get_payload() );
		}
	}
}
