<?php
/**
 * Newspack Network Content Distribution Post Update.
 *
 * @package Newspack
 */

namespace Newspack_Network\Incoming_Events;

use Newspack_Network\Debugger;
use Newspack_Network\Content_Distribution;

/**
 * Class to handle the network post update.
 */
class Network_Post_Updated extends Abstract_Incoming_Event {
	/**
	 * Processes the event
	 *
	 * @return void
	 */
	public function post_process_in_hub() {
		$this->process_post_updated();
	}

	/**
	 * Process event in Node
	 *
	 * @return void
	 */
	public function process_in_node() {
		$this->process_post_updated();
	}

	/**
	 * Process post updated
	 */
	protected function process_post_updated() {
		$post_payload = $this->get_data();
		Content_Distribution::insert_linked_post( (array) $post_payload );
	}
}
