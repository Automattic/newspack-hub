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
	 * Post meta key for the linked post containing the distributed post hash.
	 */
	const POST_HASH_META = 'newspack_network_post_hash';


	/**
	 * Post meta key for the linked post containing the distributed post full payload.
	 */
	const POST_PAYLOAD_META = 'newspack_network_post_payload';

	/**
	 * Initialize this class and register hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_listeners' ] );
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
	}

	/**
	 * Post update listener callback.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 */
	public static function handle_post_updated( $post ) {
		if ( ! self::is_post_distributed( $post ) ) {
			return;
		}
		return self::get_post_payload( $post );
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
	 * @param int   $post_id  The post ID.
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
		// Set post hash to link the post across the network.
		if ( empty( $config['post_hash'] ) ) {
			$config['post_hash'] = wp_generate_password( 32, false );
		}
		$config['enabled']   = empty( $site_urls ) ? false : true;
		$config['site_urls'] = $site_urls;
		update_post_meta( $post_id, self::DISTRIBUTED_POST_META, $config );
	}

	/**
	 * Manually trigger post distribution.
	 *
	 * @param WP_Post|int $post The post object or ID.
	 */
	public static function distribute_post( $post ) {
		if ( ! self::is_post_distributed( $post ) ) {
			return new WP_Error( 'post_not_distributed', __( 'The post is not distributed across the network.', 'newspack-network' ) );
		}
		Data_Events::dispatch( 'network_post_updated', self::get_post_payload( $post ) );
	}

	/**
	 * Whether the post is distributed. Optionally provide a $site_url to check if
	 * the post is distributed to that site.
	 *
	 * @param WP_Post|int $post    The post object or ID.
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
				'enabled'   => false,
				'site_urls'  => [],
				'post_hash' => '',
			]
		);
		return $config;
	}

	/**
	 * Get the post payload for distribution.
	 *
	 * @param WP_Post|int $post The post object.
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
			'post_id'   => $post->ID,
			'config'    => $config,
			'post_data' => [
				'title'       => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'date'        => $post->post_date,
				'slug'        => $post->post_name,
				'post_type'   => $post->post_type,
				'raw_content' => $post->post_content,
				'content'     => self::get_processed_post_content( $post ),
				'excerpt'     => $post->post_excerpt,
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
	 * @param string $post_type The post type.
	 * @param string $post_hash The distributed post hash.
	 *
	 * @return WP_Post|null The linked post or null if not found.
	 */
	protected static function get_linked_post( $post_type, $post_hash ) {
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ],
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => self::POST_HASH_META,
						'value' => $post_hash,
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
	 * Insert a linked post given a distributed post payload.
	 *
	 * @param array $post_payload The post payload.
	 *
	 * @return void
	 */
	public static function insert_linked_post( $post_payload ) {
		$config    = $post_payload['config'];
		$post_data = $post_payload['post_data'];

		// Post payload is not for a distributed post.
		if ( ! $config['enabled'] || empty( $config['post_hash'] ) || empty( $config['site_urls'] ) ) {
			return;
		}

		// Only insert the post if the site URL is in the list of site URLs.
		$site_url = get_bloginfo( 'url' );
		if ( ! in_array( $site_url, $config['site_urls'], true ) ) {
			return;
		}

		$post_hash = $config['post_hash'];
		$post_type = $post_data['post_type'];

		// Get the existing linked post.
		$linked_post = self::get_linked_post( $post_type, $post_hash );

		$postarr = [
			'ID'           => $linked_post ? $linked_post->ID : 0,
			'post_date'    => $post_payload['post_data']['date'],
			'post_title'   => $post_payload['post_data']['title'],
			'post_name'    => $post_payload['post_data']['slug'],
			'post_content' => use_block_editor_for_post_type( $post_type ) ?
				$post_payload['post_data']['raw_content'] :
				$post_payload['post_data']['content'],
			'post_excerpt' => $post_payload['post_data']['excerpt'],
			'post_type'    => $post_type,
		];

		// New post, set post status.
		if ( ! $linked_post ) {
			$post_data['post_status'] = 'draft';
		}

		// @TODO Do not insert if the post has been unlinked. The post meta should
		// still get updated so the content can be replaced once re-linked.

		$post_id = wp_insert_post( $postarr, true );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, self::POST_PAYLOAD_META, $post_payload );
		update_post_meta( $post_id, self::POST_HASH_META, $post_hash );
	}
}
