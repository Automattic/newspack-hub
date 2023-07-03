<?php
/**
 * Newspack Hub Node representation
 *
 * @package Newspack
 */

namespace Newspack_Network\Hub;

use Newspack_Network\Crypto;
use Newspack_Network\Rest_Authenticaton;
use WP_Post;

/**
 * Class to represent one Node of the netowrk
 */
class Node {

	/**
	 * The WP_Post object for this Node
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Constructs a new Node
	 *
	 * @param WP_Post|int $node A WP_Post object or a post ID.
	 */
	public function __construct( $node ) {
		if ( is_numeric( $node ) ) {
			$node = get_post( $node );
		}

		if ( ! $node instanceof WP_Post || Nodes::POST_TYPE_SLUG !== $node->post_type ) {
			return false;
		}

		$this->post = $node;
	}

	/**
	 * Returns the Node's ID
	 *
	 * @return ?int
	 */
	public function get_id() {
		if ( $this->post instanceof WP_Post && ! empty( $this->post->ID ) ) {
			return $this->post->ID;
		}
	}
	
	/**
	 * Returns the Node's URL
	 *
	 * @return ?string
	 */
	public function get_url() {
		return get_post_meta( $this->get_id(), 'node-url', true );
	}

	/**
	 * Returns the Node's Secret key
	 *
	 * @return ?string
	 */
	public function get_secret_key() {
		return get_post_meta( $this->get_id(), 'secret-key', true );
	}

	/**
	 * Returns the Node's Authorization Header to be used in REST request to it
	 *
	 * @param int $endpoint_id The ID of the endpoint to be accessed. IDs are defined in Newspack_Network\Rest_Authentication.
	 * @return ?string
	 */
	public function get_authorization_headers( $endpoint_id ) {
		return Rest_Authenticaton::generate_signature_headers( $endpoint_id, $this->get_secret_key() );
	}

	/**
	 * Returns the Node's App User
	 *
	 * @return ?string
	 */
	public function get_app_user() {
		return get_post_meta( $this->get_id(), 'app-user', true );
	}

	/**
	 * Returns the Node's App Pass
	 *
	 * @return ?string
	 */
	public function get_app_pass() {
		return get_post_meta( $this->get_id(), 'app-pass', true );
	}

	/**
	 * Verifies that a signed message was signed with this Node's secret key
	 *
	 * @param string $message The message to be verified.
	 * @param string $nonce The nonce to decrypt the message with.
	 * @return string|false The verified message or false if the message could not be verified.
	 */
	public function decrypt_message( $message, $nonce ) {
		return Crypto::decrypt_message( $message, $this->get_secret_key(), $nonce );
	}
}
