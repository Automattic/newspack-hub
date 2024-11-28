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

	const POST_META = 'newspack_network_distributed';

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
	 * @param int[] $site_ids Array of site IDs to distribute the post to.
	 *
	 * @return void
	 */
	public static function set_post_distribution( $post_id, $site_ids = [] ) {
		$config = get_post_meta( $post_id, self::POST_META, true );
		if ( ! is_array( $config ) ) {
			$config = [];
		}
		$config['enabled']  = empty( $site_ids ) ? false : true;
		$config['site_ids'] = $site_ids;
		update_post_meta( $post_id, self::POST_META, $config );
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
	 * Whether the post is distributed.
	 *
	 * @param WP_Post|int $post    The post object or ID.
	 * @param int|null    $site_id Optional site ID.
	 *
	 * @return bool
	 */
	protected static function is_post_distributed( $post, $site_id = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}

		$distributed_post_types = self::get_distributed_post_types();
		if ( ! in_array( $post->post_type, $distributed_post_types, true ) ) {
			return false;
		}

		$config = self::get_post_config( $post );
		if ( ! $config['enabled'] || empty( $config['site_ids'] ) ) {
			return false;
		}

		if ( ! empty( $site_id ) ) {
			return in_array( $site_id, $config['site_ids'], true );
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
		$config = get_post_meta( $post->ID, self::POST_META, true );
		if ( ! is_array( $config ) ) {
			$config = [];
		}
		$config = wp_parse_args(
			$config,
			[
				'enabled'  => false,
				'site_ids' => [],
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
				'title'     => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'slug'      => $post->post_name,
				'post_type' => $post->post_type,
				'content'   => self::get_post_content( $post ),
				'excerpt'   => $post->post_excerpt,
				// @ TODO: Add meta, featured image and taxonomies.
			],
		];
	}

	/**
	 * Get the post content for distribution.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return string The post content.
	 */
	protected static function get_post_content( $post ) {
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
}
