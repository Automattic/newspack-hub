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
	 * Ingest authors for a post distributed to this site
	 *
	 * @param int   $post_id The post ID.
	 * @param array $author The distributed authors array.
	 *
	 * @return void
	 */
	public static function ingest_author_for_post( int $post_id, array $author ): void {
		$author = self::get_wp_user_author( $post_id, $author );
		wp_update_post(
			[
				'ID'          => $post_id,
				'post_author' => $author->ID,
			] 
		);
	}

	/**
	 * Ingest authors for a post distributed to this site
	 *
	 * @param int   $post_id The post ID.
	 * @param array $author The distributed authors array.
	 *
	 * @return \WP_User|\WP_Error The user object or false on failure.
	 */
	public static function get_wp_user_author( int $post_id, array $author ) {

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
			get_post_meta( $post_id, 'dt_original_site_url', true ), // TODO.
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
