<?php
/**
 * Class TestOutgoingPost
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution\Outgoing_Post;

/**
 * Test the Outgoing_Post class.
 */
class TestOutgoingPoist extends WP_UnitTestCase {
	/**
	 * URL for node that receives posts.
	 *
	 * @var string
	 */
	protected $node_url = 'https://node.test';

	/**
	 * A distributed post.
	 *
	 * @var Outgoing_Post
	 */
	protected $outgoing_post;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$this->outgoing_post = new Outgoing_Post( $post );
		$this->outgoing_post->set_distribution( [ $this->node_url ] );
	}

	/**
	 * Test set post distribution.
	 */
	public function test_set_distribution() {
		$result = $this->outgoing_post->set_distribution( [ $this->node_url ] );
		$this->assertFalse( is_wp_error( $result ) );
	}

	/**
	 * Test get post distribution.
	 */
	public function test_get_distribution() {
		$distribution = $this->outgoing_post->get_distribution();
		$this->assertSame( [ $this->node_url ], $distribution );
	}

	/**
	 * Test get config for non-distributed.
	 */
	public function test_get_distribution_for_non_distributed() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$outgoing_post = new Outgoing_Post( $post );
		$distribution  = $outgoing_post->get_distribution();
		$this->assertEmpty( $distribution );
	}

	/**
	 * Test is distributed.
	 */
	public function test_is_distributed() {
		$this->assertTrue( $this->outgoing_post->is_distributed() );

		// Update the post distribution.
		$result = $this->outgoing_post->set_distribution( [] );
		$this->assertFalse( $this->outgoing_post->is_distributed() );

		// Assert regular post.
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$outgoing_post = new Outgoing_Post( $post );
		$this->assertFalse( $outgoing_post->is_distributed() );
	}

	/**
	 * Test get payload.
	 */
	public function test_get_payload() {
		$payload = $this->outgoing_post->get_payload();
		$this->assertNotEmpty( $payload );

		$distribution = $this->outgoing_post->get_distribution();

		$this->assertSame( get_bloginfo( 'url' ), $payload['site_url'] );
		$this->assertSame( $this->outgoing_post->get_post()->ID, $payload['post_id'] );
		$this->assertSame( 32, strlen( $payload['network_post_id'] ) );
		$this->assertEquals( $distribution, $payload['sites'] );

		// Assert that 'post_data' only contains the expected keys.
		$post_data_keys = [
			'title',
			'date_gmt',
			'modified_gmt',
			'slug',
			'post_type',
			'raw_content',
			'content',
			'excerpt',
			'thumbnail_url',
			'taxonomy',
		];
		$this->assertEmpty( array_diff( $post_data_keys, array_keys( $payload['post_data'] ) ) );
		$this->assertEmpty( array_diff( array_keys( $payload['post_data'] ), $post_data_keys ) );
	}
}
