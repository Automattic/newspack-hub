<?php
/**
 * Class TestContentDistributionAdmin
 *
 * @package Newspack_Network
 */

use Newspack_Network\Content_Distribution\Admin;

/**
 * Test the Content Distribution Admin class.
 */
class TestContentDistributionAdmin extends WP_UnitTestCase {
	/**
	 * Test default roles option value.
	 */
	public function test_default_roles_options() {
		$roles = get_option( Admin::CAPABILITY_ROLES_OPTION_NAME );
		$this->assertNotEmpty( $roles );
		$this->assertContains( 'administrator', $roles );
		$this->assertContains( 'editor', $roles );
		$this->assertContains( 'author', $roles );
	}

	/**
	 * Test default roles capability.
	 */
	public function test_default_roles_capability() {
		$roles = get_option( Admin::CAPABILITY_ROLES_OPTION_NAME );
		foreach ( $roles as $role ) {
			$role_obj = get_role( $role );
			$this->assertTrue( $role_obj->has_cap( Admin::CAPABILITY ) );
		}
	}

	/**
	 * Test updating roles.
	 */
	public function test_update_roles() {
		$roles = get_option( Admin::CAPABILITY_ROLES_OPTION_NAME );
		$roles[] = 'contributor';
		update_option( Admin::CAPABILITY_ROLES_OPTION_NAME, $roles );

		$role_obj = get_role( 'contributor' );
		$this->assertTrue( $role_obj->has_cap( Admin::CAPABILITY ) );

		$roles = get_option( Admin::CAPABILITY_ROLES_OPTION_NAME );
		$roles = array_diff( $roles, [ 'contributor' ] );
		update_option( Admin::CAPABILITY_ROLES_OPTION_NAME, $roles );

		$role_obj = get_role( 'contributor' );
		$this->assertFalse( $role_obj->has_cap( Admin::CAPABILITY ) );
	}
}
