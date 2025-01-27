<?php
/**
 * Newspack Network author ingestion for content distribution.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

use Newspack_Network\Debugger;
use Newspack_Network\User_Update_Watcher;
use WP_Error;
use WP_Post;

/**
 * Class to handle author ingestion for content distribution.
 */
class Cap_Authors {

	/**
	 * Meta key for Co-Authors Plus authors for networked posts.
	 */
	const CAP_AUTHORS_META_KEY = 'newspack_network_cap_authors';

	/**
	 * Get things going.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! self::is_co_authors_plus_active() ) {
			return;
		}

		add_action( 'set_object_terms', [ __CLASS__, 'handle_cap_author_change' ], 10, 6 );
	}

	/**
	 * Helper to check if Co-Authors Plus is active.
	 *
	 * @return bool Whether Co-Authors Plus is active.
	 */
	public static function is_co_authors_plus_active(): bool {
		global $coauthors_plus;

		return $coauthors_plus instanceof \CoAuthors_Plus && function_exists( 'get_coauthors' );
	}


	/**
	 * Action callback.
	 *
	 * Add a postmeta entry with the Co-Authors Plus authors for outgoing posts.
	 */
	public static function handle_cap_author_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ): void {
		if ( 'author' !== $taxonomy ) { // Co-Authors Plus author taxonomy.
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
			update_post_meta( $object_id, self::CAP_AUTHORS_META_KEY, $cap_authors );

		} catch ( \InvalidArgumentException ) {
			return;
		}
	}

	private static function get_cap_authors_for_distribution( WP_Post $post ): array {

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
	 * Ingest authors for a post distributed to this site
	 *
	 * @param int    $post_id The post ID.
	 * @param string $site_url The site URL.
	 * @param array  $cap_authors Array of distributed authors.
	 *
	 * @return void
	 */
	public static function ingest_cap_authors_for_post( int $post_id, string $site_url, array $cap_authors ): void {
		if ( ! self::is_co_authors_plus_active() ) {
			return;
		}

		$cap_authors = reset( $cap_authors ); // It comes as a multiple. TODO

		Debugger::log( 'Ingesting authors from networked post.' );
		User_Update_Watcher::$enabled = false;

		$coauthors = [];

		foreach ( $cap_authors as $author ) {
			if ( 'wp_user' === ( $author['type'] ?? '' ) ) {
				$user = Incoming_Author::get_wp_user_author( $post_id, $author );
				if ( is_wp_error( $user ) ) {
					Debugger::log( 'Error ingesting author: ' . $user->get_error_message() );
					continue;
				}
				$coauthors[] = $user->user_nicename;
				continue;
			} elseif ( 'guest-author' === ( $author['type'] ?? '' ) ) {
				// TODO. How do I get actual guest users enabled?
			}
		}

		global $coauthors_plus;
		// Do this even if the array is empty, to clear out any existing authors.
		$coauthors_plus->add_coauthors( $post_id, $coauthors );
	}

	/**
	 * Get the guest author data to be distributed along with the post.
	 *
	 * @param object $guest_author The Guest Author object.
	 *
	 * @return WP_Error|array
	 */
	private static function get_guest_author_for_distribution( $guest_author ): array|WP_Error {

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
