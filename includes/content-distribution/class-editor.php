<?php
/**
 * Newspack Network Content Distribution Editor.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Content_Distribution;
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
		add_filter( 'manage_posts_columns', [ __CLASS__, 'add_distribution_column' ], 10, 2 );
		add_action( 'manage_posts_custom_column', [ __CLASS__, 'render_distribution_column' ], 10, 2 );
		add_action( 'admin_footer', [ __CLASS__, 'add_posts_column_styles' ] );
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
						return current_user_can( Admin::CAPABILITY );
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

		if ( ! current_user_can( Admin::CAPABILITY ) ) {
			return;
		}

		$post = get_post();

		// Don't enqueue the script for incoming posts.
		if ( Content_Distribution::is_post_incoming( $post ) ) {
			return;
		}

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
				'post_type_label'  => get_post_type_labels( get_post_type_object( $screen->post_type ) )->singular_name,
			]
		);
	}

	/**
	 * Add distribution column to the posts list.
	 *
	 * @param array  $columns   Columns.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public static function add_distribution_column( $columns, $post_type ) {
		if ( ! in_array( $post_type, Content_Distribution::get_distributed_post_types(), true ) ) {
			return $columns;
		}
		$columns['content_distribution'] = sprintf(
			'<span title="%1$s">%2$s <span class="screen-reader-text">%1$s</span></span>',
			esc_attr__( 'Content Distribution', 'newspack-network' ),
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" style="margin: -2px 0 -6px;"><path d="M12 3.3c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8s-4-8.8-8.8-8.8zm6.5 5.5h-2.6C15.4 7.3 14.8 6 14 5c2 .6 3.6 2 4.5 3.8zm.7 3.2c0 .6-.1 1.2-.2 1.8h-2.9c.1-.6.1-1.2.1-1.8s-.1-1.2-.1-1.8H19c.2.6.2 1.2.2 1.8zM12 18.7c-1-.7-1.8-1.9-2.3-3.5h4.6c-.5 1.6-1.3 2.9-2.3 3.5zm-2.6-4.9c-.1-.6-.1-1.1-.1-1.8 0-.6.1-1.2.1-1.8h5.2c.1.6.1 1.1.1 1.8s-.1 1.2-.1 1.8H9.4zM4.8 12c0-.6.1-1.2.2-1.8h2.9c-.1.6-.1 1.2-.1 1.8 0 .6.1 1.2.1 1.8H5c-.2-.6-.2-1.2-.2-1.8zM12 5.3c1 .7 1.8 1.9 2.3 3.5H9.7c.5-1.6 1.3-2.9 2.3-3.5zM10 5c-.8 1-1.4 2.3-1.8 3.8H5.5C6.4 7 8 5.6 10 5zM5.5 15.3h2.6c.4 1.5 1 2.8 1.8 3.7-1.8-.6-3.5-2-4.4-3.7zM14 19c.8-1 1.4-2.2 1.8-3.7h2.6C17.6 17 16 18.4 14 19z"></path></svg>'
		);
		return $columns;
	}

	/**
	 * Render the distribution column.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public static function render_distribution_column( $column, $post_id ) {
		if ( 'content_distribution' !== $column ) {
			return;
		}

		$is_incoming = Content_Distribution::is_post_incoming( $post_id );
		$is_outgoing = Content_Distribution::is_post_distributed( $post_id );

		if ( ! $is_incoming && ! $is_outgoing ) {
			return;
		}

		$original_url      = '';
		$original_site_url = '';

		if ( $is_incoming ) {
			try {
				$incoming_post     = new Incoming_Post( $post_id );
				$linked            = $incoming_post->is_linked();
				$original_url      = $incoming_post->get_original_post_url();
				$original_site_url = $incoming_post->get_original_site_url();
			} catch ( \Exception $e ) {
				$linked = false;
			}
			printf(
				$original_url ?
					'<a href="%1$s" title="%2$s %3$s">%4$s<span class="screen-reader-text">%2$s %3$s</span></a>' :
					'<span title="%2$s">%4$s<span class="screen-reader-text">%2$s</span></span>',
				esc_url( $original_url ),
				$linked ?
					sprintf(
						// translators: %s is the original site URL.
						esc_html__( 'Originally posted and linked to %s.', 'newspack-network' ),
						esc_url( $original_site_url )
					) :
					sprintf(
						// translators: %s is the original site URL.
						esc_html__( 'Originally posted in %s and currently unlinked.', 'newspack-network' ),
						esc_url( $original_site_url )
					),
				$original_url ? esc_html__( 'Click to visit the original post.', 'newspack-network' ) : '',
				$linked ?
					'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M10 17.389H8.444A5.194 5.194 0 1 1 8.444 7H10v1.5H8.444a3.694 3.694 0 0 0 0 7.389H10v1.5ZM14 7h1.556a5.194 5.194 0 0 1 0 10.39H14v-1.5h1.556a3.694 3.694 0 0 0 0-7.39H14V7Zm-4.5 6h5v-1.5h-5V13Z"></path></svg>' :
					'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M17.031 4.703 15.576 4l-1.56 3H14v.03l-2.324 4.47H9.5V13h1.396l-1.502 2.889h-.95a3.694 3.694 0 0 1 0-7.389H10V7H8.444a5.194 5.194 0 1 0 0 10.389h.17L7.5 19.53l1.416.719L15.049 8.5h.507a3.694 3.694 0 0 1 0 7.39H14v1.5h1.556a5.194 5.194 0 0 0 .273-10.383l1.202-2.304Z"></path></svg>'
			);
		} else {
			$outgoing_post      = new Outgoing_Post( $post_id );
			$distribution_count = count( $outgoing_post->get_distribution() );
			printf(
				'<span title="%2$s">%1$s<span class="screen-reader-text">%2$s</span></span>',
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M17.3 10.1C17.3 7.60001 15.2 5.70001 12.5 5.70001C10.3 5.70001 8.4 7.10001 7.9 9.00001H7.7C5.7 9.00001 4 10.7 4 12.8C4 14.9 5.7 16.6 7.7 16.6H9.5V15.2H7.7C6.5 15.2 5.5 14.1 5.5 12.9C5.5 11.7 6.5 10.5 7.7 10.5H9L9.3 9.40001C9.7 8.10001 11 7.20001 12.5 7.20001C14.3 7.20001 15.8 8.50001 15.8 10.1V11.4L17.1 11.6C17.9 11.7 18.5 12.5 18.5 13.4C18.5 14.4 17.7 15.2 16.8 15.2H14.5V16.6H16.7C18.5 16.6 19.9 15.1 19.9 13.3C20 11.7 18.8 10.4 17.3 10.1Z M14.1245 14.2426L15.1852 13.182L12.0032 10L8.82007 13.1831L9.88072 14.2438L11.25 12.8745V18H12.75V12.8681L14.1245 14.2426Z"></path></svg>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				sprintf(
					esc_html(
						// translators: %s is the number of network sites the post has been distributed to.
						_n(
							'This post has been distributed to %d network site.',
							'This post has been distributed to %d network sites.',
							$distribution_count,
							'newspack-network'
						)
					),
					esc_attr( number_format_i18n( $distribution_count ) )
				)
			);
		}
	}

	/**
	 * Add posts columns styles.
	 */
	public static function add_posts_column_styles() {
		$screen = get_current_screen();
		if ( ! in_array( $screen->post_type, Content_Distribution::get_distributed_post_types(), true ) ) {
			return;
		}
		?>
		<style>
			.wp-list-table th#content_distribution,
			.wp-list-table .column-content_distribution {
				width: 32px;
				text-align: center;
			}
			.wp-list-table .column-content_distribution a svg {
				fill: #2271b1;
			}
		</style>
		<?php
	}
}
