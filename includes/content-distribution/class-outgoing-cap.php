<?php
/**
 * TODO.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Debugger;
use WP_Error;
use WP_Post;

/**
 * TODO
 */
class Outgoing_Cap {

	public static function init(): void {
		add_action( 'set_object_terms', [ __CLASS__, 'handle_cap_author_change' ], 10, 6 );
	}

	public static function handle_cap_author_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( $taxonomy !== 'author' ) { // Co-Authors Plus author taxonomy.
			return;
		}
		// If the terms are the same, we don't need to do anything. Note that one array has string values and one has
		// int values, so we use array_map with the intval for the comparison.
		if ( array_map( 'intval', $old_tt_ids ) === array_map( 'intval', $tt_ids ) ) {
			return;
		}

		try {
			$outgoing_post = new Outgoing_Post( $object_id );
			if ( ! $outgoing_post->is_distributed() ) {
				return;
			}

			$cap_authors = self::get_cap_authors_for_distribution( $outgoing_post->get_post() );
			update_post_meta( $object_id, 'newspack_network_cap_authors', $cap_authors );

		} catch ( \InvalidArgumentException ) {
			return;
		}

	}

	public static function get_cap_authors_for_distribution( WP_Post $post ): array {
		if ( ! function_exists( 'get_coauthors' ) ) {
			return [];
		}

		$co_authors = get_coauthors( $post->ID );
		if ( empty( $co_authors ) ) {
			return [];
		}

		$authors = [];

		foreach ( $co_authors as $co_author ) {
			if ( is_a( $co_author, 'WP_User' ) ) {
				// This will never return an error because we are checking for is_a() first.
				$authors[] = Outgoing_Author::get_wp_user_for_distribution( $co_author );
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