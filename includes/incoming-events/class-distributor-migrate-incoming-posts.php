<?php
/**
 * Newspack Network Distributor Migrate Incoming Posts.
 *
 * @package Newspack
 */

namespace Newspack_Network\Incoming_Events;

use Newspack_Network\Debugger;
use Newspack_Network\Content_Distribution\Distributor_Migrator;

/**
 * Class to handle the network post update.
 */
class Distributor_Migrate_Incoming_Posts extends Abstract_Incoming_Event {
	/**
	 * Processes the event
	 *
	 * @return void
	 */
	public function post_process_in_hub() {
		$this->process_migration();
	}

	/**
	 * Process event in Node
	 *
	 * @return void
	 */
	public function process_in_node() {
		$this->process_migration();
	}

	/**
	 * Process incoming post migration.
	 */
	protected function process_migration() {
		$data = (array) $this->get_data();

		Debugger::log( 'Processing distributor_migrate_incoming_posts ' . wp_json_encode( $data['post_ids'] ) );

		foreach ( $data['post_ids'] as $post_id ) {
			$result = Distributor_Migrator::migrate_incoming_post( $post_id );
			if ( is_wp_error( $result ) ) {
				Debugger::log(
					sprintf(
						'Error processing post ID %d: %s',
						$post_id,
						$result->get_error_message()
					)
				);
			}
		}
	}
}
