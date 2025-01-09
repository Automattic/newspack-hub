<?php
/**
 * Class TestIncomingPostContent
 *
 * @package Newspack_Network
 */

namespace Test\Content_Distribution;

use Newspack_Network\Content_Distribution\Outgoing_Post;
use Newspack_Network\Content_Distribution\Incoming_Post;

/**
 * Test the Incoming_Post class.
 */
class TestIncomingPostContent extends \WP_UnitTestCase {
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
	 * Get sample post payload.
	 */
	private function get_sample_payload() {
		return get_sample_payload( $this->node_1, $this->node_2 );
	}

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// Set the site URL for the node that receives posts.
		update_option( 'siteurl', $this->node_2 );
		update_option( 'home', $this->node_2 );
	}

	/**
	 * Get outgoing post payload with content.
	 *
	 * @param string $content The post content.
	 *
	 * @return array The outgoing post payload.
	 */
	private function get_outgoing_post_payload_with_content( $content ) {
		$outgoing_post = $this->factory->post->create_and_get( [ 'post_content' => $content ] );
		$payload       = ( new Outgoing_Post( $outgoing_post->ID ) )->get_payload();

		// Mock distribution for the post.
		$payload['site_url'] = $this->node_1;
		$payload['sites']    = [ $this->node_2 ];

		return $payload;
	}

	/**
	 * Test classic editor content.
	 */
	public function test_classic_editor_content() {
		add_filter( 'use_block_editor_for_post_type', '__return_false' );

		// Create an outgoing post with classic editor content.
		ob_start();
		?>
		<h2>Heading 2</h2>
		<img class="alignnone size-medium wp-image-123" src="https://picsum.photos/id/1/300/300.jpg" width="300" height="300" />
		<strong>Strong paragraph</strong>
		<p style="text-align: center;">Align middle</p>
		<p style="text-align: right;">Align right</p>
		<ul>
			<li>List Item #1</li>
			<li>List Item #2</li>
		</ul>
		<ol>
			<li>Ordered List Item #1</li>
			<li>Ordered List Item #2</li>
		</ol>
		<a href="https://newspack.com">Link</a>
		<?php
		$payload = $this->get_outgoing_post_payload_with_content( ob_get_clean() );

		$incoming_post = new Incoming_Post( $payload );
		$post_id       = $incoming_post->insert();

		$this->assertEquals( get_the_content( $payload['post_id'] ), get_the_content( $post_id ) );

		remove_filter( 'use_block_editor_for_post_type', '__return_false' );
	}

	/**
	 * Test gallery block content.
	 */
	public function test_gallery_block_content() {
		$payload = $this->get_sample_payload();

		ob_start();
		?>
		<!-- wp:gallery {"linkTo":"none"} -->
		<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
			<!-- wp:image {"id":123,"sizeSlug":"large","linkDestination":"none","meta":{"_media_credit":"","_media_credit_url":"","_navis_media_credit_org":""}} -->
			<figure class="wp-block-image size-large"><img src="https://picsum.photos/id/1/300/300.jpg" alt="" class="wp-image-123"/><figcaption class="wp-element-caption">Test 1</figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:image {"id":456,"sizeSlug":"large","linkDestination":"none","meta":{"_media_credit":"","_media_credit_url":"","_navis_media_credit_org":""}} -->
			<figure class="wp-block-image size-large"><img src="https://picsum.photos/id/2/300/300.jpg" alt="" class="wp-image-456"/><figcaption class="wp-element-caption">Test 2</figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:image {"id":789,"sizeSlug":"large","linkDestination":"none","meta":{"_media_credit":"","_media_credit_url":"","_navis_media_credit_org":""}} -->
			<figure class="wp-block-image size-large"><img src="https://picsum.photos/id/3/300/300.jpg" alt="" class="wp-image-789"/><figcaption class="wp-element-caption">Test 3</figcaption></figure>
			<!-- /wp:image -->

			<!-- wp:image {"id":012,"sizeSlug":"large","linkDestination":"none","meta":{"_media_credit":"","_media_credit_url":"","_navis_media_credit_org":""}} -->
			<figure class="wp-block-image size-large"><img src="https://picsum.photos/id/4/300/300.jpg" alt="" class="wp-image-012"/><figcaption class="wp-element-caption">Test 4</figcaption></figure>
			<!-- /wp:image -->
		</figure>
		<!-- /wp:gallery -->
		<?php
		$payload = $this->get_outgoing_post_payload_with_content( ob_get_clean() );

		$incoming_post = new Incoming_Post( $payload );
		$post_id       = $incoming_post->insert();

		$this->assertNotEmpty( $post_id );
		$this->assertEquals( get_the_content( $payload['post_id'] ), get_the_content( $post_id ) );
	}
}
