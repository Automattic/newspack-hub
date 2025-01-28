<?php
/**
 * Newspack Network filters for making guest authors work.
 *
 * @package Newspack
 */

namespace Newspack_Network\Content_Distribution;

/**
 * Class to handle authorship filters
 * TODO
 */
class Cap_Authors_Filters {

	/**
	 * Go!
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! Cap_Authors::is_co_authors_plus_active() ) {
			return;
		}
		if ( ! is_admin() ) {
			add_filter( 'get_coauthors', [ __CLASS__, 'filter_get_coauthors' ], 10, 2 );
			add_filter( 'newspack_author_bio_name', [ __CLASS__, 'filter_newspack_author_bio_name' ], 10, 3 );
			add_filter( 'author_link', [ __CLASS__, 'filter_author_link' ], 20, 3 );
		}
	}

	/**
	 * Filters the coauthors of a post to include the distributed authors and CAP's guest authors
	 *
	 * @param array $coauthors Array of coauthors.
	 * @param int   $post_id Post ID.
	 *
	 * @return array
	 */
	public static function filter_get_coauthors( $coauthors, $post_id ) {
		if ( empty( get_post_meta( $post_id, Incoming_Post::NETWORK_POST_ID_META, true ) ) ) {
			return $coauthors;
		}

		$distributed_authors = get_post_meta( $post_id, Cap_Authors::AUTHOR_LIST_META_KEY, true );

		if ( ! $distributed_authors ) {
			return $coauthors;
		}

		$guest_authors = [];

		foreach ( $distributed_authors as $distributed_author ) {

			if ( 'guest_author' !== $distributed_author['type'] ) {
				continue;
			}
			// This removes the author URL from the guest author.
			$distributed_author['user_nicename'] = '';
			$distributed_author['ID']            = - 2;

			$guest_authors[] = (object) $distributed_author;
		}

		return [ ...$coauthors, ...$guest_authors ];
	}

	/**
	 * Add job title for guest authors in the author bio.
	 *
	 * @param string $author_name The author name.
	 * @param int    $author_id The author ID.
	 * @param object $author The author object.
	 */
	public static function filter_newspack_author_bio_name( $author_name, $author_id, $author = null ) {
		if ( empty( $author->type ) || 'guest_author' !== $author->type ) {
			return $author_name;
		}

		if ( $author && ! empty( $author->newspack_job_title ) ) {
			$author_name .= '<span class="author-job-title">' . $author->newspack_job_title . '</span>';
		}

		return $author_name;
	}

	/**
	 * Filter the author link for guest authors.
	 *
	 * @param string $link The author link.
	 * @param int    $author_id The author ID.
	 * @param string $author_nicename The author nicename.
	 *
	 * @return string
	 */
	public static function filter_author_link( $link, $author_id, $author_nicename ) {
		if ( - 2 === $author_id && empty( $author_nicename ) ) {
			$link = '#';
		}

		return $link;
	}
}
