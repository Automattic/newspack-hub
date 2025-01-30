<?php
/**
 * Newspack Network author ingestion for content distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Debugger;
use Newspack_Network\User_Update_Watcher;
use Newspack_Network\Utils\Users as User_Utils;

/**
 * Class to handle author ingestion for content distribution.
 */
class Incoming_Author {

	/**
	 * Get the post author for an incoming post.
	 *
	 * @param int    $post_id    The post ID.
	 * @param string $remote_url The remote URL.
	 * @param array  $author     The distributed authors array.
	 *
	 * @return int The author ID - or zero if none was found.
	 */
	public static function get_incoming_post_author( int $post_id, string $remote_url, array $author ): int {
		if ( empty( $author ) ) {
			return 0;
		}
		$author = self::get_wp_user_author( $remote_url, $author );
		if ( is_wp_error( $author ) ) {
			return 0;
		}

		return $author->ID;
	}

	/**
	 * Ingest authors for a post distributed to this site
	 *
	 * @param string $remote_url The remote site URL.
	 * @param array  $author     The distributed authors array.
	 *
	 * @return \WP_User|\WP_Error The user object or false on failure.
	 */
	public static function get_wp_user_author( string $remote_url, array $author ) {

		User_Update_Watcher::$enabled = false;

		$insert_array = [
			'role' => 'author',
		];

		foreach ( User_Update_Watcher::$user_props as $prop ) {
			if ( isset( $author[ $prop ] ) ) {
				$insert_array[ $prop ] = $author[ $prop ];
			}
		}

		$user = User_Utils::get_or_create_user_by_email(
			$author['user_email'],
			$remote_url,
			$author['ID'],
			$insert_array
		);

		if ( is_wp_error( $user ) ) {
			Debugger::log( 'Error creating user: ' . $user->get_error_message() );

			return $user;
		}

		foreach ( User_Update_Watcher::get_writable_meta() as $meta_key ) {
			if ( isset( $author[ $meta_key ] ) ) {
				update_user_meta( $user->ID, $meta_key, $author[ $meta_key ] );
			}
		}

		User_Utils::maybe_sideload_avatar( $user->ID, $author, false );

		return $user;
	}
}
