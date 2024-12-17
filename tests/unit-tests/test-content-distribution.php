<?php
/**
 * Class TestContentDistribution
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution;
use Newspack_Network\Content_Distribution\Outgoing_Post;
use Newspack_Network\Hub\Node as Hub_Node;

/**
 * Test the Content_Distribution class.
 */
class TestContentDistribution extends WP_UnitTestCase {
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
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// "Mock" the network node(s).
		update_option( Hub_Node::HUB_NODES_SYNCED_OPTION, $this->network );
	}

	/**
	 * Test update distributed post meta.
	 */
	public function test_update_distributed_post_meta() {
		$post_id = $this->factory->post->create();

		// Assert that you're not allowed to update the meta with a non-network site.
		$result = update_post_meta( $post_id, Outgoing_Post::DISTRIBUTED_POST_META, [ 'http://non-network-site.com' ] );
		$this->assertFalse( $result );

		// Assert that you're allowed to update the meta with a network site.
		$result = update_post_meta( $post_id, Outgoing_Post::DISTRIBUTED_POST_META, [ 'https://node.test' ] );
		$this->assertNotFalse( $result );

		// Assert that you can't remove a site from distribution.
		$result = update_post_meta( $post_id, Outgoing_Post::DISTRIBUTED_POST_META, [ 'https://other-node.test' ] );
		$this->assertFalse( $result );

		// Assert that you can add a site to distribution.
		$result = update_post_meta( $post_id, Outgoing_Post::DISTRIBUTED_POST_META, [ 'https://node.test', 'https://other-node.test' ] );
		$this->assertNotFalse( $result );
	}
}
