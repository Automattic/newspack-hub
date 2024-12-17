<?php
/**
 * Newspack Network Content Distribution Post Update.
 *
 * @package Newspack
 */

namespace Newspack_Network\Incoming_Events;

use Newspack_Network\Debugger;
use Newspack_Network\Content_Distribution\Incoming_Post;

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
		$payload = (array) $this->get_data();

		Debugger::log( 'Processing network_post_updated ' . wp_json_encode( $payload['sites'] ) );

		$error = Incoming_Post::get_payload_error( $payload );
		if ( is_wp_error( $error ) ) {
			Debugger::log( 'Error processing network_post_updated: ' . $error->get_error_message() );
			return;
		}
		$incoming_post = new Incoming_Post( $payload );
		$incoming_post->insert();
	}
}
