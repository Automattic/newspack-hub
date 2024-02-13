<?php
/**
 * Newspack User Synced Incoming Event class
 *
 * @package Newspack
 */

namespace Newspack_Network\Incoming_Events;

use Newspack_Network\User_Manual_Sync;
use Newspack_Network\Debugger;
use Newspack_Network\Utils\Users as User_Utils;

/**
 * Class to handle the User Updated Event
 *
 * This event is always sent from the Hub and received by Nodes.
 */
class User_Synced extends Abstract_Incoming_Event {

	/**
	 * Processes the event
	 *
	 * @return void
	 */
	public function post_process_in_hub() {
		$this->maybe_sync_user();
	}

	/**
	 * Process event in Node
	 *
	 * @return void
	 */
	public function process_in_node() {
		$this->maybe_sync_user();
	}

	/**
	 * Maybe updates a new WP user based on this event
	 *
	 * @return void
	 */
	public function maybe_sync_user() {
		$email = $this->get_email();
		Debugger::log( 'Processing user_synced with email: ' . $email );
		if ( ! $email ) {
			return;
		}

		$existing_user = get_user_by( 'email', $email );

		if ( ! $existing_user ) {
			Debugger::log( 'User not found, skipping.' );
			return;
		}

		$data = $this->get_data();

		if ( isset( $data->prop ) ) {
			$update_array = [
				'ID' => $existing_user->ID,
			];
			foreach ( $data->prop as $prop_key => $prop_value ) {
				$update_array[ $prop_key ] = $prop_value;
			}
			Debugger::log( 'Updating user with data: ' . print_r( $update_array, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			wp_update_user( $update_array );
		}

		if ( isset( $data->meta ) ) {
			foreach ( $data->meta as $meta_key => $meta_value ) {
				Debugger::log( 'Updating user meta: ' . $meta_key );
				update_user_meta( $existing_user->ID, $meta_key, $meta_value );
			}
		}

		// User_Utils::maybe_sideload_avatar( $existing_user->ID, $data->meta, true );
	}
}
