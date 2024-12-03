<?php
/**
 * Newspack Network Content Distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network;

use Newspack\Data_Events;
use WP_Post;
use WP_Error;

/**
 * Main class for content distribution
 */
class Content_Distribution {

	/**
	 * The post meta key for the distributed post configuration.
	 */
	const DISTRIBUTED_POST_META = 'newspack_network_distributed';

	/**
	 * Post meta key for the linked post containing a post ID unique accross the network.
	 */
	const NETWORK_POST_ID_META = 'newspack_network_post_id';

	/**
	 * Post meta key for the linked post containing the distributed post full payload.
	 */
	const POST_PAYLOAD_META = 'newspack_network_post_payload';

	/**
	 * Post meta key for the linked post to determine whether the post is unlinked.
	 */
	const POST_UNLINKED_META = 'newspack_network_post_unlinked';

	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_listeners' ] );
		add_filter( 'newspack_webhooks_request_priority', [ __CLASS__, 'webhooks_request_priority' ], 10, 2 );
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
		Data_Events::register_listener( 'newspack_network_linked_post_inserted', 'network_linked_post_inserted', [ __CLASS__, 'handle_linked_post_inserted' ] );
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
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return array|null The post payload or null if the post is not distributed.
	 */
	public static function handle_post_updated( $post ) {
		if ( ! self::is_post_distributed( $post ) ) {
			return;
		}

		$payload = self::get_post_payload( $post );
		if ( is_wp_error( $payload ) ) {
			return;
		}

		return $payload;
	}

	/**
	 * Linked post inserted listener callback.
	 *
	 * @param int     $post_id      The linked post ID.
	 * @param boolean $is_unlinked  Whether the post is unlinked.
	 * @param array   $post_payload The post payload.
	 */
	public static function handle_linked_post_inserted( $post_id, $is_unlinked, $post_payload ) {
		return [
			'origin'      => [
				'site_url' => $post_payload['site_url'],
				'post_id'  => $post_payload['post_id'],
			],
			'destination' => [
				'site_url'    => get_bloginfo( 'url' ),
				'post_id'     => $post_id,
				'is_unlinked' => $is_unlinked,
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
	 * Set the distribution configuration for a given post.
	 *
	 * @param int   $post_id   The post ID.
	 * @param int[] $site_urls Array of site URLs to distribute the post to.
	 *
	 * @return void|WP_Error Void on success, WP_Error on failure.
	 */
	public static function set_post_distribution( $post_id, $site_urls = [] ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post.', 'newspack-network' ) );
		}
		$config = get_post_meta( $post_id, self::DISTRIBUTED_POST_META, true );
		if ( ! is_array( $config ) ) {
			$config = [];
		}
		// Set post network ID.
		if ( empty( $config['network_post_id'] ) ) {
			$config['network_post_id'] = md5( $post_id . get_bloginfo( 'url' ) );
		}
		$config['enabled']   = empty( $site_urls ) ? false : true;
		$config['site_urls'] = $site_urls;
		update_post_meta( $post_id, self::DISTRIBUTED_POST_META, $config );
	}

	/**
	 * Manually trigger post distribution.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return void
	 */
	public static function distribute_post( $post ) {
		$data = self::handle_post_updated( $post );
		if ( $data ) {
			Data_Events::dispatch( 'network_post_updated', $data );
		}
	}

	/**
	 * Whether the post is distributed. Optionally provide a $site_url to check if
	 * the post is distributed to that site.
	 *
	 * @param WP_Post|int $post     The post object or ID.
	 * @param int|null    $site_url Optional site ID.
	 *
	 * @return bool
	 */
	protected static function is_post_distributed( $post, $site_url = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}

		$distributed_post_types = self::get_distributed_post_types();
		if ( ! in_array( $post->post_type, $distributed_post_types, true ) ) {
			return false;
		}

		$config = self::get_post_config( $post );
		if ( ! $config['enabled'] || empty( $config['site_urls'] ) ) {
			return false;
		}

		if ( ! empty( $site_url ) ) {
			return in_array( $site_url, $config['site_urls'], true );
		}

		return true;
	}

	/**
	 * Get the distribution configuration for a given post.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return array The distribution configuration.
	 */
	protected static function get_post_config( $post ) {
		$config = get_post_meta( $post->ID, self::DISTRIBUTED_POST_META, true );
		if ( ! is_array( $config ) ) {
			$config = [];
		}
		$config = wp_parse_args(
			$config,
			[
				'enabled'         => false,
				'site_urls'       => [],
				'network_post_id' => '',
			]
		);
		return $config;
	}

	/**
	 * Get the post payload for distribution.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 *
	 * @return array|WP_Error The post payload or WP_Error if the post is invalid.
	 */
	protected static function get_post_payload( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post.', 'newspack-network' ) );
		}

		$config = self::get_post_config( $post );
		return [
			'site_url'  => get_bloginfo( 'url' ),
			'post_id'   => $post->ID,
			'config'    => $config,
			'post_data' => [
				'title'        => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'date_gmt'     => $post->post_date_gmt,
				'modified_gmt' => $post->post_modified_gmt,
				'slug'         => $post->post_name,
				'post_type'    => $post->post_type,
				'raw_content'  => $post->post_content,
				'content'      => self::get_processed_post_content( $post ),
				'excerpt'      => $post->post_excerpt,
				// @ TODO: Add meta, featured image and taxonomies.
			],
		];
	}

	/**
	 * Get the processed post content for distribution.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return string The post content.
	 */
	protected static function get_processed_post_content( $post ) {
		global $wp_embed;
		/**
		 * Remove autoembed filter so that actual URL will be pushed and not the generated markup.
		 */
		remove_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
		// Filter documented in WordPress core.
		$post_content = apply_filters( 'the_content', $post->post_content );
		add_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
		return $post_content;
	}

	/**
	 * Get a linked post given the distributed post hash.
	 *
	 * @param string $post_type       The post type.
	 * @param string $network_post_id The network post ID for the distributed post.
	 *
	 * @return WP_Post|null The linked post or null if not found.
	 */
	protected static function get_linked_post( $post_type, $network_post_id ) {
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ],
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => self::NETWORK_POST_ID_META,
						'value' => $network_post_id,
					],
				],
			]
		);
		if ( empty( $posts ) ) {
			return null;
		}
		return $posts[0];
	}

	/**
	 * Set a post as unlinked.
	 *
	 * This will prevent the post from being updated when the distributed post is
	 * updated.
	 *
	 * @param int  $post_id  The post ID.
	 * @param bool $unlinked Whether to set the post as unlinked. Default is true.
	 *
	 * @return void|WP_Error Void on success, WP_Error on failure.
	 */
	public static function set_post_unlinked( $post_id, $unlinked = true ) {
		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post.', 'newspack-network' ) );
		}
		update_post_meta( $post_id, self::POST_UNLINKED_META, (bool) $unlinked );

		// If the post is being re-linked, update content.
		if ( ! $unlinked ) {
			$post_payload = get_post_meta( $post_id, self::POST_PAYLOAD_META, true );
			self::insert_linked_post( $post_payload );
		}
	}

	/**
	 * Whether a post is unlinked.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool
	 */
	protected static function is_post_unlinked( $post_id ) {
		return get_post_meta( $post_id, self::POST_UNLINKED_META, true );
	}

	/**
	 * Insert a linked post given a distributed post payload.
	 *
	 * @param array $post_payload The post payload.
	 *
	 * @return int|WP_Error The linked post ID or WP_Error on failure.
	 */
	public static function insert_linked_post( $post_payload ) {
		if ( ! is_array( $post_payload ) || empty( $post_payload['post_id'] ) || empty( $post_payload['config'] ) || empty( $post_payload['post_data'] ) ) {
			return new WP_Error( 'invalid_post_payload', __( 'Invalid post payload.', 'newspack-network' ) );
		}

		$config    = $post_payload['config'];
		$post_data = $post_payload['post_data'];

		// Post payload is not for a distributed post.
		if ( ! $config['enabled'] || empty( $config['network_post_id'] ) || empty( $config['site_urls'] ) ) {
			return new WP_Error( 'invalid_post_payload', __( 'Invalid post payload.', 'newspack-network' ) );
		}

		// Only insert the post if the site URL is in the list of site URLs.
		$site_url = get_bloginfo( 'url' );
		if ( ! in_array( $site_url, $config['site_urls'], true ) ) {
			return new WP_Error( 'invalid_site', __( 'Post is not configured to be distributed to this site.', 'newspack-network' ) );
		}

		$network_post_id = $config['network_post_id'];
		$post_type = $post_data['post_type'];

		// Get the existing linked post.
		$linked_post = self::get_linked_post( $post_type, $network_post_id );

		/**
		 * Post updates may not come in order, so we need to check if the linked
		 * post payload is newer than the distributed post. If it is, we don't
		 * update the linked post.
		 */
		if ( $linked_post ) {
			$linked_post_payload = get_post_meta( $linked_post->ID, self::POST_PAYLOAD_META, true );
			if ( $linked_post_payload && $linked_post_payload['post_data']['modified_gmt'] > $post_data['modified_gmt'] ) {
				return $linked_post->ID;
			}
		}

		$postarr = [
			'ID'            => $linked_post ? $linked_post->ID : 0,
			'post_date_gmt' => $post_payload['post_data']['date_gmt'],
			'post_title'    => $post_payload['post_data']['title'],
			'post_name'     => $post_payload['post_data']['slug'],
			'post_content'  => use_block_editor_for_post_type( $post_type ) ?
				$post_payload['post_data']['raw_content'] :
				$post_payload['post_data']['content'],
			'post_excerpt'  => $post_payload['post_data']['excerpt'],
			'post_type'     => $post_type,
		];

		// New post, set post status.
		if ( ! $linked_post ) {
			$postarr['post_status'] = 'draft';
		}

		// Insert the post if it's linked or a new post.
		$is_unlinked = $linked_post ? self::is_post_unlinked( $linked_post->ID ) : false;
		if ( ! $linked_post || ! $is_unlinked ) {
			// Remove filters that may alter content updates.
			remove_all_filters( 'content_save_pre' );

			$post_id = wp_insert_post( $postarr, true );
		} else {
			$post_id = $linked_post->ID;
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// The wp_insert_post() function might return `0` on failure.
		if ( ! $post_id ) {
			return new WP_Error( 'insert_error', __( 'Error inserting post.', 'newspack-network' ) );
		}

		update_post_meta( $post_id, self::POST_PAYLOAD_META, $post_payload );
		update_post_meta( $post_id, self::NETWORK_POST_ID_META, $network_post_id );

		/**
		 * Fires after a linked post is inserted.
		 *
		 * @param int     $post_id      The linked post ID.
		 * @param boolean $is_unlinked  Whether the post is unlinked.
		 * @param array   $post_payload The post payload.
		 */
		do_action( 'newspack_network_linked_post_inserted', $post_id, $is_unlinked, $post_payload );

		return $post_id;
	}
}
