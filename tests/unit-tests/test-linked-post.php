<?php
/**
 * Class TestContentDistribution
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution;
use Newspack_Network\Content_Distribution\Distributed_Post;
use Newspack_Network\Content_Distribution\Linked_Post;

/**
 * Test the Content Distribution class.
 */
class TestLinkedPost extends WP_UnitTestCase {

	/**
	 * URL for node that distributes posts.
	 *
	 * @var string
	 */
	protected $node_1 = 'https://node1.test';

	/**
	 * URL for node that receives posts.
	 *
	 * @var string
	 */
	protected $node_2 = 'https://node2.test';

	/**
	 * A distributed post.
	 *
	 * @var Content_Distribution\Distributed_Post
	 */
	protected $distributed_post;

	/**
	 * A linked post.
	 *
	 * @var Content_Distribution\Linked_Post
	 */
	protected $linked_post;

	/**
	 * Get sample post payload.
	 */
	private function get_sample_payload() {
		return [
			'site_url'  => $this->node_1,
			'post_id'   => 1,
			'config'    => [
				'enabled'         => true,
				'site_urls'       => [ $this->node_2 ],
				'network_post_id' => '1234567890abcdef1234567890abcdef',
			],
			'post_data' => [
				'title'         => 'Title',
				'date_gmt'      => '2021-01-01 00:00:00',
				'modified_gmt'  => '2021-01-01 00:00:00',
				'slug'          => 'slug',
				'post_type'     => 'post',
				'raw_content'   => 'Content',
				'content'       => '<p>Content</p>',
				'excerpt'       => 'Excerpt',
				'thumbnail_url' => 'https://picsum.photos/id/1/300/300.jpg',
				'taxonomy'      => [
					'category' => [
						[
							'name' => 'Category 1',
							'slug' => 'category-1',
						],
						[
							'name' => 'Category 2',
							'slug' => 'category-2',
						],
					],
					'post_tag' => [
						[
							'name' => 'Tag 1',
							'slug' => 'tag-1',
						],
						[
							'name' => 'Tag 2',
							'slug' => 'tag-2',
						],
					],
				],
			],
		];
	}

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// Set the site URL for the node that receives posts.
		update_option( 'siteurl', $this->node_2 );
		update_option( 'home', $this->node_2 );

		$this->linked_post = new Linked_Post( $this->get_sample_payload() );
	}

	/**
	 * Test get payload error
	 */
	public function test_validate_payload() {
		$payload = $this->get_sample_payload();
		$error = Linked_Post::get_payload_error( $payload );
		$this->assertFalse( is_wp_error( $error ) );

		// Assert with invalid post.
		$error = Linked_Post::get_payload_error( [] );
		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 'invalid_post', $error->get_error_code() );

		// Assert with invalid site.
		update_option( 'siteurl', $this->node_1 );
		update_option( 'home', $this->node_1 );
		$error = Linked_Post::get_payload_error( $payload );
		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 'not_distributed_to_site', $error->get_error_code() );

		// Assert invalid config.
		$payload['config'] = 'invalid';
		$error = Linked_Post::get_payload_error( $payload );
		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 'not_distributed', $error->get_error_code() );
	}

	/**
	 * Test insert linked post.
	 */
	public function test_insert() {
		$this->assertEmpty( $this->linked_post->ID );

		$post_id = $this->linked_post->insert();

		$this->assertNotEmpty( $this->linked_post->ID );

		$this->assertFalse( is_wp_error( $post_id ) );
		$this->assertGreaterThan( 0, $post_id );

		$payload = $this->get_sample_payload();
		$this->assertSame( $payload['post_data']['date_gmt'], get_the_date( 'Y-m-d H:i:s', $post_id ) );
		$this->assertSame( $payload['post_data']['title'], get_the_title( $post_id ) );
		$this->assertSame( $payload['post_data']['raw_content'], get_post_field( 'post_content', $post_id ) );
		$this->assertNotEmpty( get_post_thumbnail_id( $post_id ) );

		// Assert taxonomy terms.
		$terms = wp_get_post_terms( $post_id, [ 'category', 'post_tag' ] );
		$this->assertSame( [ 'Category 1', 'Category 2', 'Tag 1', 'Tag 2' ], wp_list_pluck( $terms, 'name' ) );
		$this->assertSame( [ 'category-1', 'category-2', 'tag-1', 'tag-2' ], wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * Test insert existing linked post.
	 */
	public function test_insert_existing_post() {
		// Insert the linked post for the first time.
		$post_id = $this->linked_post->insert();

		// Modify the post payload to simulate an update.
		$payload = $this->get_sample_payload();
		$payload['post_data']['title'] = 'Updated Title';
		$payload['post_data']['content'] = 'Updated Content';
		$payload['post_data']['raw_content'] = 'Updated Content';

		// Insert the updated linked post.
		$new_linked_post = new Linked_Post( $payload );
		$updated_post_id = $new_linked_post->insert();

		// Assert that the updated post has the same ID as the original post.
		$this->assertSame( $post_id, $updated_post_id );

		// Assert that the updated post has the updated title and content.
		$linked_post = get_post( $post_id );
		$this->assertSame( 'Updated Title', $linked_post->post_title );
		$this->assertSame( 'Updated Content', $linked_post->post_content );
	}

	/**
	 * Test insert post when unlinked.
	 */
	public function test_insert_post_when_unlinked() {
		$post_id = $this->linked_post->insert();

		// Unlink the post.
		$this->linked_post->set_unlinked();

		// Update linked post with custom content.
		$this->factory->post->update_object(
			$post_id,
			[
				'post_title'   => 'Custom Title',
				'post_content' => 'Custom Content',
			]
		);

		// Modify the post payload for an update.
		$payload = $this->get_sample_payload();
		$payload['post_data']['title'] = 'Updated Title';
		$payload['post_data']['content'] = 'Updated Content';
		$payload['post_data']['raw_content'] = 'Updated Content';

		// Insert the updated linked post.
		$this->linked_post->insert( $payload );

		// Assert that the custom content was preserved.
		$linked_post = get_post( $post_id );
		$this->assertSame( 'Custom Title', $linked_post->post_title );
		$this->assertSame( 'Custom Content', $linked_post->post_content );
	}

	/**
	 * Test relink post.
	 */
	public function test_relink_post() {
		$post_id = $this->linked_post->insert();

		// Unlink the post.
		$this->linked_post->set_unlinked();

		// Update linked post with custom content.
		$this->factory->post->update_object(
			$post_id,
			[
				'post_title'   => 'Custom Title',
				'post_content' => 'Custom Content',
			]
		);

		// Relink the post.
		$this->linked_post->set_unlinked( false );

		// Assert that the post is linked and distributed content restored.
		$payload = $this->get_sample_payload();
		$this->assertSame( $payload['post_data']['title'], get_the_title( $post_id ) );
		$this->assertSame( $payload['post_data']['raw_content'], get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * Test insert post with old modified date.
	 */
	public function test_insert_post_with_old_modified_date() {
		// Insert the linked post for the first time.
		$post_id = $this->linked_post->insert();

		// Modify the post payload to simulate an update with an old modified date.
		$payload = $this->get_sample_payload();
		$payload['post_data']['title'] = 'Old Title';
		$payload['post_data']['modified_gmt'] = '2020-01-01 00:00:00';

		// Insert the updated linked post.
		$error = $this->linked_post->insert( $payload );

		// Assert that the insertion returned an error.
		$this->assertTrue( is_wp_error( $error ) );
		$this->assertSame( 'old_modified_date', $error->get_error_code() );

		// Assert that the linked post kept the most recent title.
		$linked_post = get_post( $post_id );
		$this->assertSame( 'Title', $linked_post->post_title );
	}

	/**
	 * Test update post thumbnail.
	 */
	public function test_update_post_thumbnail() {
		$post_id = $this->linked_post->insert();

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		// Set a different thumbnail URL.
		$payload = $this->get_sample_payload();
		$payload['post_data']['thumbnail_url'] = 'https://picsum.photos/id/2/300/300.jpg';

		// Insert the linked post with the updated thumbnail.
		$this->linked_post->insert( $payload );

		// Assert that the thumbnail was updated.
		$new_thumbnail_id = get_post_thumbnail_id( $post_id );

		$this->assertNotEmpty( $new_thumbnail_id );
		$this->assertNotEquals( $thumbnail_id, $new_thumbnail_id );
	}

	/**
	 * Test remove post thumbnail.
	 */
	public function test_remove_post_thumbnail() {
		$post_id = $this->linked_post->insert();

		// Remove the thumbnail.
		$payload = $this->get_sample_payload();
		$payload['post_data']['thumbnail_url'] = false;

		// Insert the linked post with the removed thumbnail.
		$this->linked_post->insert( $payload );

		// Assert that the thumbnail was removed.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$this->assertEmpty( $thumbnail_id );
	}
}
