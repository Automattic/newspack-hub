<?php
/**
 * Class TestContentDistribution
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution;

/**
 * Test the Content Distribution class.
 */
class TestContentDistribution extends WP_UnitTestCase {
	/**
	 * Test set post distribution.
	 */
	public function test_set_post_distribution() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$result = Content_Distribution::set_post_distribution( $post->ID, [ 'https://example.com' ] );
		$this->assertFalse( is_wp_error( $result ) );

		$config = get_post_meta( $post->ID, Content_Distribution::DISTRIBUTED_POST_META, true );
		$this->assertTrue( $config['enabled'] );
		$this->assertSame( [ 'https://example.com' ], $config['site_urls'] );
		$this->assertSame( 32, strlen( $config['network_post_id'] ) );
	}

	/**
	 * Test set post distribution persists the network post ID.
	 */
	public function test_set_post_distribution_persists_network_post_id() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$result = Content_Distribution::set_post_distribution( $post->ID, [ 'https://example.com' ] );
		$config = get_post_meta( $post->ID, Content_Distribution::DISTRIBUTED_POST_META, true );

		// Update the post distribution.
		$result = Content_Distribution::set_post_distribution( $post->ID, [ 'https://example2.com' ] );
		$new_config = get_post_meta( $post->ID, Content_Distribution::DISTRIBUTED_POST_META, true );

		$this->assertSame( $config['network_post_id'], $new_config['network_post_id'] );
	}

	/**
	 * Test set post distribution with invalid post.
	 */
	public function test_set_post_distribution_with_invalid_post() {
		$result = Content_Distribution::set_post_distribution( 0, [ 'https://example.com' ] );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'invalid_post', $result->get_error_code() );
	}

	/**
	 * Test handle post updated.
	 */
	public function test_handle_post_updated() {
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		Content_Distribution::set_post_distribution( $post->ID, [ 'https://example.com' ] );

		$post_payload = Content_Distribution::handle_post_updated( $post );
		$this->assertNotEmpty( $post_payload );

		$config = get_post_meta( $post->ID, Content_Distribution::DISTRIBUTED_POST_META, true );

		$this->assertSame( get_bloginfo( 'url' ), $post_payload['site_url'] );
		$this->assertSame( $post->ID, $post_payload['post_id'] );
		$this->assertEquals( $config, $post_payload['config'] );

		// Assert that 'post_data' only contains the expected keys.
		$post_data_keys = [ 'title', 'date', 'slug', 'post_type', 'raw_content', 'content', 'excerpt', 'taxonomy' ];
		$this->assertEmpty( array_diff( $post_data_keys, array_keys( $post_payload['post_data'] ) ) );
		$this->assertEmpty( array_diff( array_keys( $post_payload['post_data'] ), $post_data_keys ) );
	}

	/**
	 * Test handle post updated with invalid post.
	 */
	public function test_handle_post_updated_with_invalid_post() {
		// Post that is not distributed.
		$post = $this->factory->post->create_and_get( [ 'post_type' => 'post' ] );
		$post_payload = Content_Distribution::handle_post_updated( $post );
		$this->assertNull( $post_payload );

		// Missing post.
		$post_payload = Content_Distribution::handle_post_updated( 0 );
		$this->assertNull( $post_payload );
	}

	/**
	 * Get sample post payload.
	 */
	private function get_sample_post_payload() {
		return [
			'site_url'  => 'https://hub.com',
			'post_id'   => 1,
			'config'    => [
				'enabled'         => true,
				'site_urls'       => [ 'https://example.com' ],
				'network_post_id' => '1234567890abcdef1234567890abcdef',
			],
			'post_data' => [
				'title'       => 'Title',
				'date'        => '2021-01-01 00:00:00',
				'slug'        => 'slug',
				'post_type'   => 'post',
				'raw_content' => 'Content',
				'content'     => '<p>Content</p>',
				'excerpt'     => 'Excerpt',
				'taxonomy'    => [
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
	 * Test insert linked post.
	 */
	public function test_insert_linked_post() {
		$post_payload = $this->get_sample_post_payload();

		// Update blog URL to match the distributed post.
		update_option( 'siteurl', 'https://example.com' );
		update_option( 'home', 'https://example.com' );

		// Insert the linked post.
		$linked_post_id = Content_Distribution::insert_linked_post( $post_payload );
		$this->assertGreaterThan( 0, $linked_post_id );

		// Assert taxonomy terms.
		$terms = wp_get_post_terms( $linked_post_id, [ 'category', 'post_tag' ] );
		$this->assertSame( [ 'Category 1', 'Category 2', 'Tag 1', 'Tag 2' ], wp_list_pluck( $terms, 'name' ) );
		$this->assertSame( [ 'category-1', 'category-2', 'tag-1', 'tag-2' ], wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * Test insert linked post with invalid payload.
	 */
	public function test_insert_linked_post_with_invalid_payload() {
		$linked_post_id = Content_Distribution::insert_linked_post( [] );
		$this->assertTrue( is_wp_error( $linked_post_id ) );
		$this->assertSame( 'invalid_post_payload', $linked_post_id->get_error_code() );
	}

	/**
	 * Test insert linked post with invalid site.
	 */
	public function test_insert_linked_post_with_invalid_site() {
		$post_payload = $this->get_sample_post_payload();

		// Update blog URL to not match the distributed post.
		update_option( 'siteurl', 'https://example2.com' );
		update_option( 'home', 'https://example2.com' );

		// Insert the linked post.
		$linked_post_id = Content_Distribution::insert_linked_post( $post_payload );
		$this->assertTrue( is_wp_error( $linked_post_id ) );
		$this->assertSame( 'invalid_site', $linked_post_id->get_error_code() );
	}

	/**
	 * Test insert existing linked post.
	 */
	public function test_insert_existing_linked_post() {
		$post_payload = $this->get_sample_post_payload();

		// Update blog URL to match the distributed post.
		update_option( 'siteurl', 'https://example.com' );
		update_option( 'home', 'https://example.com' );

		// Insert the linked post for the first time.
		$linked_post_id = Content_Distribution::insert_linked_post( $post_payload );

		// Modify the post payload to simulate an update.
		$post_payload['post_data']['title'] = 'Updated Title';
		$post_payload['post_data']['content'] = 'Updated Content';
		$post_payload['post_data']['raw_content'] = 'Updated Content';

		// Insert the updated linked post.
		$updated_linked_post_id = Content_Distribution::insert_linked_post( $post_payload );

		// Assert that the updated post has the same ID as the original post.
		$this->assertSame( $linked_post_id, $updated_linked_post_id );

		// Assert that the updated post has the updated title and content.
		$linked_post = get_post( $updated_linked_post_id );
		$this->assertSame( 'Updated Title', $linked_post->post_title );
		$this->assertSame( 'Updated Content', $linked_post->post_content );
	}

	/**
	 * Test insert linked post when unlinked.
	 */
	public function test_insert_linked_post_when_unlinked() {
		$post_payload = $this->get_sample_post_payload();

		// Update blog URL to match the distributed post.
		update_option( 'siteurl', 'https://example.com' );
		update_option( 'home', 'https://example.com' );

		// Insert the linked post for the first time.
		$linked_post_id = Content_Distribution::insert_linked_post( $post_payload );

		// Unlink the post.
		Content_Distribution::set_post_unlinked( $linked_post_id );

		// Update linked post with custom content.
		$this->factory->post->update_object(
			$linked_post_id,
			[
				'post_title'   => 'Custom Title',
				'post_content' => 'Custom Content',
			]
		);

		// Modify the post payload to simulate an update.
		$post_payload['post_data']['title'] = 'Updated Title';
		$post_payload['post_data']['content'] = 'Updated Content';
		$post_payload['post_data']['raw_content'] = 'Updated Content';

		// Insert the updated linked post.
		$updated_linked_post_id = Content_Distribution::insert_linked_post( $post_payload );

		// Assert that the custom content was preserved.
		$linked_post = get_post( $updated_linked_post_id );
		$this->assertSame( 'Custom Title', $linked_post->post_title );
		$this->assertSame( 'Custom Content', $linked_post->post_content );
	}

	/**
	 * Test relink post.
	 */
	public function test_relink_post() {
		$post_payload = $this->get_sample_post_payload();

		// Update blog URL to match the distributed post.
		update_option( 'siteurl', 'https://example.com' );
		update_option( 'home', 'https://example.com' );

		// Insert the linked post for the first time.
		$linked_post_id = Content_Distribution::insert_linked_post( $post_payload );

		// Unlink the post.
		Content_Distribution::set_post_unlinked( $linked_post_id );

		// Update linked post with custom content.
		$this->factory->post->update_object(
			$linked_post_id,
			[
				'post_title'   => 'Custom Title',
				'post_content' => 'Custom Content',
			]
		);

		// Relink the post.
		Content_Distribution::set_post_unlinked( $linked_post_id, false );

		// Assert that the post is linked and distributed content restored.
		$this->assertSame( $post_payload['post_data']['title'], get_the_title( $linked_post_id ) );
		$this->assertSame( $post_payload['post_data']['raw_content'], get_post_field( 'post_content', $linked_post_id ) );
	}
}
