<?php
/**
 * Newspack Network Content Distribution Editor.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
use Newspack_Network\Utils\Network;
use WP_Post;

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
					'auth_callback' => function () {
						return current_user_can( Admin::CAPABILITY );
					},
				]
			);
		}
	}

	/**
	 * Action callback.
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_assets(): void {
		$screen = get_current_screen();
		if (
			! current_user_can( Admin::CAPABILITY )
			|| ! in_array( $screen->post_type, Content_Distribution::get_distributed_post_types(), true )
		) {
			return;
		}

		$post = get_post();

		if ( Content_Distribution::is_post_incoming( $post ) ) {
			self::enqueue_block_editor_assets_for_incoming_post( $post );
		} else {
			self::enqueue_block_editor_assets_for_outgoing_post( $post );
		}
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @param WP_Post $post The post being edited.
	 *
	 * @return void
	 */
	private static function enqueue_block_editor_assets_for_incoming_post( WP_Post $post ): void {

		$incoming = new Incoming_Post( $post->ID );

		wp_enqueue_script(
			'newspack-network-incoming-post',
			plugins_url( '../../dist/incoming-post.js', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_DIR . 'dist/incoming-post.js' ),
			true
		);
		wp_register_style(
			'newspack-network-incoming-post',
			plugins_url( '../../dist/incoming-post.css', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_DIR . 'dist/incoming-post.css' ),
		);
		wp_style_add_data( 'newspack-network-incoming-post', 'rtl', 'replace' );
		wp_enqueue_style( 'newspack-network-incoming-post' );

		wp_localize_script(
			'newspack-network-incoming-post',
			'newspack_network_incoming_post',
			[
				'original_url' => $incoming->get_original_site_url(),
				'is_linked'    => $incoming->is_linked(),
			]
		);
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @param WP_Post $post The post being edited.
	 *
	 * @return void
	 */
	private static function enqueue_block_editor_assets_for_outgoing_post( WP_Post $post ): void {
		wp_enqueue_script(
			'newspack-network-distribute',
			plugins_url( '../../dist/distribute.js', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_DIR . 'dist/distribute.js' ),
			true
		);
		wp_register_style(
			'newspack-network-distribute',
			plugins_url( '../../dist/distribute.css', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_DIR . 'dist/distribute.css' ),
		);
		wp_style_add_data( 'newspack-network-distribute', 'rtl', 'replace' );
		wp_enqueue_style( 'newspack-network-distribute' );

		wp_localize_script(
			'newspack-network-distribute',
			'newspack_network_distribute',
			[
				'network_sites'    => Network::get_networked_urls(),
				'distributed_meta' => Outgoing_Post::DISTRIBUTED_POST_META,
				'post_type_label'  => get_post_type_labels( get_post_type_object( $post->post_type ) )->singular_name,
			]
		);
	}
}
