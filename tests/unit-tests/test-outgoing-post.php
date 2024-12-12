<?php
/**
 * Class TestOutgoingPost
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution\Outgoing_Post;
use Newspack_Network\Hub\Node as Hub_Node;

/**
 * Test the Outgoing_Post class.
 */
class TestOutgoingPost extends WP_UnitTestCase {


	/**
	 * "Mocked" network nodes.
	 *
	 * @var array
	 */
	protected $network = [
		[
			'id'    => 1234,
			'title' => 'Test Node',
			'url'   => 'https://node.test',
		],
		[
			'id'    => 5678,
			'title' => 'Test Node 2',
			'url'   => 'https://other-node.test',
		],
	];

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

		// "Mock" the network node(s).
		update_option( Hub_Node::HUB_NODES_SYNCED_OPTION, $this->network );
		$post                = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$this->outgoing_post = new Outgoing_Post( $post );
		$this->outgoing_post->set_config( [ $this->network[0]['url'] ] );
	}

	/**
	 * Test adding a site URL to the config after already having added one.
	 */
	public function test_add_site_url() {
		$config = $this->outgoing_post->get_config();
		$this->assertTrue( in_array( $this->network[0]['url'], $config['site_urls'], true ) );
		$this->assertEquals( 1, count( $config['site_urls'] ) );

		// Now add one more site URL.
		$this->outgoing_post->set_config( [ $this->network[1]['url'] ] );
		$config = $this->outgoing_post->get_config();
		// Check that both urls are there.
		$this->assertTrue( in_array( $this->network[0]['url'], $config['site_urls'], true ) );
		$this->assertTrue( in_array( $this->network[1]['url'], $config['site_urls'], true ) );
		// But no more than that.
		$this->assertEquals( 2, count( $config['site_urls'] ) );
	}

	/**
	 * Test set post distribution configuration.
	 */
	public function test_set_config() {
		$result = $this->outgoing_post->set_config( [ $this->network[0]['url'] ] );
		$this->assertFalse( is_wp_error( $result ) );
	}

	/**
	 * Test get config.
	 */
	public function test_get_config() {
		$config = $this->outgoing_post->get_config();
		$this->assertSame( [ $this->network[0]['url'] ], $config['site_urls'] );
		$this->assertSame( 32, strlen( $config['network_post_id'] ) );
	}

	/**
	 * Test get config for non-distributed.
	 */
	public function test_get_config_for_non_distributed() {
		$post          = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$outgoing_post = new Outgoing_Post( $post );
		$config        = $outgoing_post->get_config();
		$this->assertEmpty( $config['site_urls'] );
		$this->assertEmpty( $config['network_post_id'] );
	}

	/**
	 * Test set post distribution persists the network post ID.
	 */
	public function test_set_config_persists_network_post_id() {
		$horse = '';
		$this->outgoing_post->set_config( [ $this->network[0]['url'] ] );
		$config = $this->outgoing_post->get_config();

		// Update the post distribution with one more node.
		$this->outgoing_post->set_config( [ $this->network[1]['url'] ] );
		$new_config = $this->outgoing_post->get_config();

		$this->assertSame( $config['network_post_id'], $new_config['network_post_id'] );
	}

	/**
	 * Test is distributed.
	 */
	public function test_is_distributed() {
		// Assert regular post.
		$post          = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
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
			'post_meta',
		];
		$this->assertEmpty( array_diff( $post_data_keys, array_keys( $payload['post_data'] ) ) );
		$this->assertEmpty( array_diff( array_keys( $payload['post_data'] ), $post_data_keys ) );
	}

	/**
	 * Test post meta.
	 */
	public function test_post_meta() {
		$post = $this->outgoing_post->get_post();
		$meta_key   = 'test_meta_key';
		$meta_value = 'test_meta_value';
		update_post_meta( $post->ID, $meta_key, $meta_value );

		$arr_meta_key = 'test_arr_meta_key';
		$arr_meta_value = [ 1, 2, 3 ];
		update_post_meta( $post->ID, $arr_meta_key, $arr_meta_value );

		$multiple_meta_key = 'test_multiple_meta_key';
		add_post_meta( $post->ID, $multiple_meta_key, 'a' );
		add_post_meta( $post->ID, $multiple_meta_key, 'b' );

		$payload = $this->outgoing_post->get_payload();
		$this->assertArrayHasKey( $meta_key, $payload['post_data']['post_meta'] );

		$this->assertSame( $meta_value, $payload['post_data']['post_meta'][ $meta_key ][0] );

		$this->assertArrayHasKey( $arr_meta_key, $payload['post_data']['post_meta'] );
		$this->assertSame( $arr_meta_value, $payload['post_data']['post_meta'][ $arr_meta_key ][0] );

		$this->assertArrayHasKey( $multiple_meta_key, $payload['post_data']['post_meta'] );
		$this->assertSame( 'a', $payload['post_data']['post_meta'][ $multiple_meta_key ][0] );
		$this->assertSame( 'b', $payload['post_data']['post_meta'][ $multiple_meta_key ][1] );
	}
}
