<?php
/**
 * Newspack Network Content Distribution: Outgoing Post.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use WP_Post;
use WP_Error;

/**
 * Outgoing Post Class.
 */
class Outgoing_Post {
	/**
	 * The post meta key for the sites the post is distributed to.
	 */
	const DISTRIBUTED_POST_META = 'newspack_network_distributed_sites';

	/**
	 * The post object.
	 *
	 * @var WP_Post
	 */
	protected $post = null;

	/**
	 * Constructor.
	 *
	 * @param WP_Post|int $post The post object or post ID.
	 *
	 * @throws \InvalidArgumentException If the post is invalid.
	 */
	public function __construct( $post ) {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post || empty( $post->ID ) ) {
			throw new \InvalidArgumentException( esc_html( __( 'Invalid post.', 'newspack-network' ) ) );
		}

		$this->post = $post;
	}

	/**
	 * Get the post object.
	 *
	 * @return WP_Post The post object.
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Get network post ID.
	 *
	 * @return string The network post ID.
	 */
	public function get_network_post_id() {
		return md5( $this->post->ID . get_bloginfo( 'url' ) );
	}

	/**
	 * Set the distribution configuration for a given post.
	 *
	 * @param int[] $site_urls Array of site URLs to distribute the post to.
	 *
	 * @return void|WP_Error Void on success, WP_Error on failure.
	 */
	public function set_distribution( $site_urls = [] ) {
		$distribution = get_post_meta( $this->post->ID, self::DISTRIBUTED_POST_META, true );
		if ( ! is_array( $distribution ) ) {
			$distribution = [];
		}
		$distribution = $site_urls;
		update_post_meta( $this->post->ID, self::DISTRIBUTED_POST_META, $distribution );
	}

	/**
	 * Whether the post is distributed. Optionally provide a $site_url to check if
	 * the post is distributed to that site.
	 *
	 * @param string|null $site_url Optional site URL.
	 *
	 * @return bool
	 */
	public function is_distributed( $site_url = null ) {
		$distributed_post_types = Content_Distribution::get_distributed_post_types();
		if ( ! in_array( $this->post->post_type, $distributed_post_types, true ) ) {
			return false;
		}

		$distribution = $this->get_distribution();
		if ( empty( $distribution ) ) {
			return false;
		}

		if ( ! empty( $site_url ) ) {
			return in_array( $site_url, $distribution, true );
		}

		return true;
	}

	/**
	 * Get the sites the post is distributed to.
	 *
	 * @return array The distribution configuration.
	 */
	public function get_distribution() {
		$config = get_post_meta( $this->post->ID, self::DISTRIBUTED_POST_META, true );
		if ( ! is_array( $config ) ) {
			$config = [];
		}
		return $config;
	}

	/**
	 * Get the post payload for distribution.
	 *
	 * @return array|WP_Error The post payload or WP_Error if the post is invalid.
	 */
	public function get_payload() {
		return [
			'site_url'        => get_bloginfo( 'url' ),
			'post_id'         => $this->post->ID,
			'network_post_id' => $this->get_network_post_id(),
			'sites'           => $this->get_distribution(),
			'post_data'       => [
				'title'         => html_entity_decode( get_the_title( $this->post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'date_gmt'      => $this->post->post_date_gmt,
				'modified_gmt'  => $this->post->post_modified_gmt,
				'slug'          => $this->post->post_name,
				'post_type'     => $this->post->post_type,
				'raw_content'   => $this->post->post_content,
				'content'       => $this->get_processed_post_content(),
				'excerpt'       => $this->post->post_excerpt,
				'taxonomy'      => $this->get_post_taxonomy_terms(),
				'thumbnail_url' => get_the_post_thumbnail_url( $this->post->ID, 'full' ),
				'post_meta'     => $this->get_post_meta(),
			],
		];
	}

	/**
	 * Get the processed post content for distribution.
	 *
	 * @return string The post content.
	 */
	protected function get_processed_post_content() {
		global $wp_embed;
		/**
		 * Remove autoembed filter so that actual URL will be pushed and not the generated markup.
		 */
		remove_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
		// Filter documented in WordPress core.
		$post_content = apply_filters( 'the_content', $this->post->post_content );
		add_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
		return $post_content;
	}

	/**
	 * Get post taxonomy terms for distribution.
	 *
	 * @return array The taxonomy term data.
	 */
	protected function get_post_taxonomy_terms() {
		$taxonomies = get_object_taxonomies( $this->post->post_type, 'objects' );
		$data       = [];
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}
			$terms = get_the_terms( $this->post->ID, $taxonomy->name );
			if ( ! $terms ) {
				continue;
			}
			$data[ $taxonomy->name ] = array_map(
				function( $term ) {
					return [
						'name' => $term->name,
						'slug' => $term->slug,
					];
				},
				$terms
			);
		}
		return $data;
	}

	/**
	 * Get post meta for distribution.
	 *
	 * @return array The post meta data.
	 */
	protected function get_post_meta() {
		$reserved_keys = Content_Distribution::get_reserved_post_meta_keys();

		$meta = get_post_meta( $this->post->ID );

		if ( empty( $meta ) ) {
			return [];
		}

		$meta = array_filter(
			$meta,
			function( $value, $key ) use ( $reserved_keys ) {
				// Filter out reserved keys.
				return ! in_array( $key, $reserved_keys, true );
			},
			ARRAY_FILTER_USE_BOTH
		);

		// Unserialize meta values and reformat the array.
		$meta = array_reduce(
			array_keys( $meta ),
			function( $carry, $key ) use ( $meta ) {
				$carry[ $key ] = array_map(
					function( $v ) {
						return maybe_unserialize( $v );
					},
					$meta[ $key ]
				);
				return $carry;
			},
			[]
		);

		/**
		 * Filters the post meta data for distribution.
		 *
		 * @param array   $meta The post meta data.
		 * @param WP_Post $post The post object.
		 */
		return apply_filters( 'newspack_network_distributed_post_meta', $meta, $this->post );
	}
}
