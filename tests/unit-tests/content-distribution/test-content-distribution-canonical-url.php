<?php
/**
 * Class TestContentDistributionCanonicalUrl
 *
 * @package Newspack_Network
 */

namespace Test\Content_Distribution;

use Newspack_Network\Content_Distribution\Incoming_Post;

/**
 * Test the Content Distribution Canonical URL class.
 */
class TestContentDistributionCanonicalUrl extends \WP_UnitTestCase {
	/**
	 * Test default canonical URL.
	 */
	public function test_default_canonical_url() {
		$sample_payload = get_sample_payload( '', get_bloginfo( 'url' ) );
		$incoming_post  = new Incoming_Post( $sample_payload );
		$post_id        = $incoming_post->insert( $sample_payload );

		wp_publish_post( $post_id );

		$this->assertEquals( $sample_payload['post_url'], wp_get_canonical_url( get_post( $post_id ) ) );
	}

	/**
	 * Test custom canonical URL base.
	 */
	public function test_custom_canonical_url() {
		update_option( 'newspack_network_canonical_url', 'https://custom.test' );

		$sample_payload = get_sample_payload( '', get_bloginfo( 'url' ) );
		$incoming_post  = new Incoming_Post( $sample_payload );
		$post_id        = $incoming_post->insert( $sample_payload );

		wp_publish_post( $post_id );

		$this->assertEquals( 'https://custom.test/2021/01/slug', wp_get_canonical_url( get_post( $post_id ) ) );
	}
}
