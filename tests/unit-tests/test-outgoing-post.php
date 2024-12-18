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
		$this->outgoing_post->set_distribution( [ $this->network[0]['url'] ] );
	}

	/**
	 * Test adding a site URL to the config after already having added one.
	 */
	public function test_add_site_url() {
		$distribution = $this->outgoing_post->get_distribution();
		$this->assertTrue( in_array( $this->network[0]['url'], $distribution, true ) );
		$this->assertEquals( 1, count( $distribution ) );

		// Now add one more site URL.
		$this->outgoing_post->set_distribution( [ $this->network[1]['url'] ] );
		$distribution = $this->outgoing_post->get_distribution();
		// Check that both urls are there.
		$this->assertTrue( in_array( $this->network[0]['url'], $distribution, true ) );
		$this->assertTrue( in_array( $this->network[1]['url'], $distribution, true ) );
		// But no more than that.
		$this->assertEquals( 2, count( $distribution ) );
	}

	/**
	 * Test set post distribution.
	 */
	public function test_set_distribution() {
		$result = $this->outgoing_post->set_distribution( [ $this->network[1]['url'] ] );
		$this->assertFalse( is_wp_error( $result ) );
	}

	/**
	 * Test non-published post.
	 */
	public function test_non_published_post() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post', 'post_status' => 'draft' ] ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		// Assert the instantiating an Outgoing_Post throws an exception.
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Only published post are allowed to be distributed.' );
		new Outgoing_Post( $post );
	}

	/**
	 * Test get post distribution.
	 */
	public function test_get_distribution() {
		$distribution = $this->outgoing_post->get_distribution();
		$this->assertSame( [ $this->network[0]['url'] ], $distribution );
	}

	/**
	 * Test get distribution for non-distributed.
	 */
	public function test_get_distribution_for_non_distributed() {
		$post          = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$outgoing_post = new Outgoing_Post( $post );
		$distribution  = $outgoing_post->get_distribution();
		$this->assertEmpty( $distribution );
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

		$distribution = $this->outgoing_post->get_distribution();

		$this->assertSame( get_bloginfo( 'url' ), $payload['site_url'] );
		$this->assertSame( $this->outgoing_post->get_post()->ID, $payload['post_id'] );
		$this->assertSame( get_permalink( $this->outgoing_post->get_post()->ID ), $payload['post_url'] );
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

	/**
	 * Test reserved taxonomies.
	 */
	public function test_reserved_taxonomies() {
		$post = $this->outgoing_post->get_post();
		$taxonomy = 'author';
		register_taxonomy( $taxonomy, 'post', [ 'public' => true ] );

		$term = $this->factory->term->create( [ 'taxonomy' => $taxonomy ] );
		wp_set_post_terms( $post->ID, [ $term ], $taxonomy );

		$payload = $this->outgoing_post->get_payload();
		$this->assertTrue( empty( $payload['post_data']['taxonomy'][ $taxonomy ] ) );
	}
}
