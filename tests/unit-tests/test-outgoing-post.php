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
		$this->outgoing_post->set_config( [ $this->node_url ] );
	}

	/**
	 * Test set post distribution configuration.
	 */
	public function test_set_config() {
		$result = $this->outgoing_post->set_config( [ $this->node_url ] );
		$this->assertFalse( is_wp_error( $result ) );
	}

	/**
	 * Test get config.
	 */
	public function test_get_config() {
		$config = $this->outgoing_post->get_config();
		$this->assertSame( [ $this->node_url ], $config['site_urls'] );
		$this->assertSame( 32, strlen( $config['network_post_id'] ) );
	}

	/**
	 * Test get config for non-distributed.
	 */
	public function test_get_config_for_non_distributed() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$outgoing_post = new Outgoing_Post( $post );
		$config           = $outgoing_post->get_config();
		$this->assertEmpty( $config['site_urls'] );
		$this->assertEmpty( $config['network_post_id'] );
	}

	/**
	 * Test set post distribution persists the network post ID.
	 */
	public function test_set_config_persists_network_post_id() {
		$result = $this->outgoing_post->set_config( [ $this->node_url ] );
		$config = $this->outgoing_post->get_config();

		// Update the post distribution.
		$result     = $this->outgoing_post->set_config( [ 'https://other-node.test' ] );
		$new_config = $this->outgoing_post->get_config();

		$this->assertSame( $config['network_post_id'], $new_config['network_post_id'] );
	}

	/**
	 * Test is distributed.
	 */
	public function test_is_distributed() {
		$this->assertTrue( $this->outgoing_post->is_distributed() );

		// Update the post distribution.
		$result = $this->outgoing_post->set_config( [] );
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

		$config = $this->outgoing_post->get_config();

		$this->assertSame( get_bloginfo( 'url' ), $payload['site_url'] );
		$this->assertSame( $this->outgoing_post->get_post()->ID, $payload['post_id'] );
		$this->assertEquals( $config, $payload['config'] );

		// Assert that 'post_data' only contains the expected keys.
		$post_data_keys = [
			'title',
			'post_status',
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
