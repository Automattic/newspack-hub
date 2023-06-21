<?php
/**
 * Newspack Hub Event Log Admin page
 *
 * @package Newspack
 */

namespace Newspack_Network\Hub\Admin;

use Newspack_Network\Admin as Network_Admin;

/**
 * Class to handle the Event log admin page
 */
class Event_Log {

	const PAGE_SLUG = 'newspack-network-event-log';

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
	}
	
	/**
	 * Adds the admin page
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		Network_Admin::add_submenu_page( __( 'Event Log', 'newspack-network' ), self::PAGE_SLUG, [ __CLASS__, 'render_page' ] );
	}

	/**
	 * Enqueues the admin styles.
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts() {
		$page_slug = Network_Admin::PAGE_SLUG . '_page_' . self::PAGE_SLUG;
		if ( get_current_screen()->id !== $page_slug ) {
			return;
		}
		
		wp_enqueue_style(
			'newspack-network-event-log',
			plugins_url( 'css/event-log.css', __FILE__ ),
			[],
			filemtime( NEWSPACK_NETWORK_PLUGIN_DIR . '/includes/hub/admin/css/event-log.css' )
		);
	}

	/**
	 * Renders the admin page
	 *
	 * @return void
	 */
	public static function render_page() {

		$table = new Event_Log_List_Table();

		echo '<div class="wrap"><h2>', esc_html( __( 'Event Log', 'newspack-network' ) ), '</h2>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_SLUG ) . '" />';
		
		$table->prepare_items();

		$table->search_box( 'search', 'search_id' );
		
		$table->display();
		
		echo '</div></form>';
	}
}
