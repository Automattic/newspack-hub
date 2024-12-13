<?php
/**
 * Newspack Network Content Distribution Editor.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use Newspack_Network\Content_Distribution\Outgoing_Post;
use Newspack_Network\Utils\Network;

/**
 * Editor Class.
 */
class Editor {
	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_meta' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_block_editor_assets' ] );
		add_filter( 'update_post_metadata', [ __CLASS__, 'update_post_metadata' ], 10, 5 );
	}

	/**
	 * Register the meta available in the editor.
	 */
	public static function register_meta() {
		$post_types = Content_Distribution::get_distributed_post_types();
		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				Outgoing_Post::DISTRIBUTED_POST_META,
				[
					'single'        => true,
					'type'          => 'array',
					'show_in_rest'  => [
						'schema' => [
							'context' => [ 'edit' ],
							'type'    => 'array',
							'default' => [],
							'items'   => [
								'type' => 'string',
							],
						],
					],
					'auth_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}

	/**
	 * Control distributed post metadata update.
	 *
	 * @param null|bool $check      Whether to allow updating metadata for the given type. Default null.
	 * @param int       $object_id  Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Metadata value.
	 * @param mixed     $prev_value Previous value to check before updating.
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( Outgoing_Post::DISTRIBUTED_POST_META !== $meta_key ) {
			return $check;
		}

		// Ensure the post type can be distributed.
		$post_types = Content_Distribution::get_distributed_post_types();
		$post_type  = get_post_type( $object_id );
		if ( ! in_array( $post_type, $post_types, true ) ) {
			return false;
		}

		$error = Outgoing_Post::validate_distribution( $meta_value );
		if ( $error ) {
			return false;
		}

		// Prevent removing existing distributions.
		$diff = array_diff( (array) $prev_value, $meta_value );
		if ( ! empty( $diff ) ) {
			return false;
		}

		return $check;
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets() {
		$screen = get_current_screen();
		if ( ! in_array( $screen->post_type, Content_Distribution::get_distributed_post_types(), true ) ) {
			return;
		}

		wp_enqueue_script(
			'newspack-network-distribute',
			plugins_url( '../../dist/distribute.js', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_FILE . 'dist/distribute.js' ),
			true
		);

		wp_localize_script(
			'newspack-network-distribute',
			'newspack_network_distribute',
			[
				'network_sites'    => Network::get_networked_urls(),
				'distributed_meta' => Outgoing_Post::DISTRIBUTED_POST_META,
			]
		);
	}
}
