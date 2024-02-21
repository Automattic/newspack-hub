<?php
/**
 * Newspack Hub plugin administration screen handling.
 *
 * @package Newspack
 */

namespace Newspack_Network\Hub;

/**
 * Class to handle the plugin admin pages
 */
class Admin {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		Admin\Event_Log::init();
		Admin\Subscriptions::init();
		Admin\Orders::init();
		Admin\Memberships::init();
		Admin\Users::init();
		Admin\Nodes_List::init();
		Distributor_Settings::init();
	}
}
