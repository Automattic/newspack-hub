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
class Outgoing_Authors {

	/**
	 * Get the authors of a post to be added to the distribution payload.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return array An array of authors.
	 */
	public static function get_authors_for_distribution( $post ): array {
		$author = self::get_wp_user_for_distribution( $post->post_author );

		if ( ! function_exists( 'get_coauthors' ) ) {
			if ( is_wp_error( $author ) ) {
				Debugger::log( 'Error getting author ' . $post->post_author . ' for distribution on post ' . $post->ID . ': ' . $author->get_error_message() );

				return [];
			}

			return [ $author ];
		}

		$co_authors = get_coauthors( $post->ID );
		if ( empty( $co_authors ) ) {
			if ( is_wp_error( $author ) ) {
				Debugger::log( 'Error getting author ' . $post->post_author . ' for distribution on post ' . $post->ID . ': ' . $author->get_error_message() );

				return [];
			}

			return [ $author ];
		}

		$authors = [];

		foreach ( $co_authors as $co_author ) {
			if ( is_a( $co_author, 'WP_User' ) ) {
				// This will never return an error because we are checking for is_a() first.
				$authors[] = self::get_wp_user_for_distribution( $co_author );
				continue;
			}

			$guest_author = self::get_guest_author_for_distribution( $co_author );
			if ( is_wp_error( $guest_author ) ) {
				Debugger::log( 'Error getting guest author for distribution on post ' . $post->ID . ': ' . $guest_author->get_error_message() );
				Debugger::log( $co_author );
				continue;
			}
			$authors[] = $guest_author;
		}

		return $authors;
	}

	/**
	 * Gets the user data of a WP user to be distributed along with the post.
	 *
	 * @param int|WP_Post $user The user ID or object.
	 *
	 * @return WP_Error|array
	 */
	private static function get_wp_user_for_distribution( $user ) {
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

	/**
	 * Get the guest author data to be distributed along with the post.
	 *
	 * @param object $guest_author The Guest Author object.
	 *
	 * @return WP_Error|array
	 */
	private static function get_guest_author_for_distribution( $guest_author ) {

		// CoAuthors plugin existence was checked in get_authors_for_distribution().
		global $coauthors_plus;

		if ( ! is_object( $guest_author ) || ! isset( $guest_author->type ) || 'guest-author' !== $guest_author->type ) {
			return new WP_Error( 'Error getting guest author details for distribution. Invalid Guest Author' );
		}

		$author         = (array) $guest_author;
		$author['type'] = 'guest_author';

		// Gets the guest author avatar.
		// We only want to send an actual uploaded avatar, we don't want to send the fallback avatar, like gravatar.
		// If no avatar was set, let it default to the fallback set in the target site.
		$author_avatar = $coauthors_plus->guest_authors->get_guest_author_thumbnail( $guest_author, 80 );
		if ( $author_avatar ) {
			$author['avatar_img_tag'] = $author_avatar;
		}

		return $author;
	}
}
