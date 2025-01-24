<?php
/**
 * Newspack Network author distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Debugger;
use Newspack_Network\User_Update_Watcher;
use WP_Error;
use WP_Post;

/**
 * Class to handle author distribution.
 *
 * Every time a post is distributed, we also send all the information about the author (or authors if CAP is enabled)
 * On the target site, the plugin will create the authors if they don't exist, and override the byline
 */
class Outgoing_Author {

	/**
	 * Gets the user data of a WP user to be distributed along with the post.
	 *
	 * @param int|WP_Post $user The user ID or object.
	 *
	 * @return WP_Error|array
	 */
	public static function get_wp_user_for_distribution( $user ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			$user = get_user_by( 'ID', $user );
		}

		if ( ! $user ) {
			return new WP_Error( 'Error getting WP User details for distribution. Invalid User' );
		}

		$author = [
			'type' => 'wp_user',
			'ID'   => $user->ID,
		];

		foreach ( User_Update_Watcher::$user_props as $prop ) {
			if ( isset( $user->$prop ) ) {
				$author[ $prop ] = $user->$prop;
			}
		}

		// CoAuthors' guest authors have a 'website' property.
		if ( isset( $user->website ) ) {
			$author['website'] = $user->website;
		}

		foreach ( User_Update_Watcher::$watched_meta as $meta_key ) {
			$author[ $meta_key ] = get_user_meta( $user->ID, $meta_key, true );
		}

		return $author;
	}

}
