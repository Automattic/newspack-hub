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
						return current_user_can( 'edit_posts' ); // @TODO Custom capability.
					},
				]
			);
		}
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
