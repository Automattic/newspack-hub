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
class Author_Ingestion {

	/**
	 * Gets the CoAuthors Plus main object, if present
	 *
	 * @return false|\CoAuthors_Plus
	 */
	public static function get_coauthors_plus() {
		global $coauthors_plus;
		if ( ! $coauthors_plus instanceof \CoAuthors_Plus ) {
			return false;
		}

		return $coauthors_plus;
	}

	/**
	 * Ingest authors for a post distributed to this site
	 *
	 * @param int   $post_id The post ID.
	 * @param array $distributed_authors The distributed authors array.
	 *
	 * @return void
	 */
	public static function ingest_authors_for_post( $post_id, $distributed_authors ) {

		Debugger::log( 'Ingesting authors from distributed post.' );

		User_Update_Watcher::$enabled = false;

		update_post_meta( $post_id, 'newspack_network_authors', $distributed_authors );

		$coauthors_plus = self::get_coauthors_plus();
		$coauthors      = [];

		foreach ( $distributed_authors as $author ) {
			// We only ingest WP Users. Guest authors are only stored in the newspack_network_authors post meta.
			if ( empty( $author['type'] ) || 'wp_user' != $author['type'] ) {
				continue;
			}

			Debugger::log( 'Ingesting author: ' . $author['user_email'] );

			$insert_array = [
				'role' => 'author',
			];

			foreach ( User_Update_Watcher::$user_props as $prop ) {
				if ( isset( $author[ $prop ] ) ) {
					$insert_array[ $prop ] = $author[ $prop ];
				}
			}

			$user = User_Utils::get_or_create_user_by_email( $author['user_email'], get_post_meta( $post_id, 'dt_original_site_url', true ), $author['ID'], $insert_array );

			if ( is_wp_error( $user ) ) {
				Debugger::log( 'Error creating user: ' . $user->get_error_message() );
				continue;
			}

			foreach ( User_Update_Watcher::get_writable_meta() as $meta_key ) {
				if ( isset( $author[ $meta_key ] ) ) {
					update_user_meta( $user->ID, $meta_key, $author[ $meta_key ] );
				}
			}

			User_Utils::maybe_sideload_avatar( $user->ID, $author, false );

			// If CoAuthors Plus is not present, just assign the first author as the post author.
			if ( ! $coauthors_plus ) {
				Debugger::log( 'CoAuthors Plus not present, assigning first author as post author.' );
				wp_update_post(
					[
						'ID'          => $post_id,
						'post_author' => $user->ID,
					]
				);
				break;
			}

			$coauthors[] = $user->user_nicename;
		}

		if ( $coauthors_plus ) {
			Debugger::log( 'CoAuthors Plus present, assigning coauthors:' );
			Debugger::log( $coauthors );
			$coauthors_plus->add_coauthors( $post_id, $coauthors );
		}
	}
}
