<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\Role;
use Agency_Pass\UserProfile;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the UserProfile class.
 */
class UserProfileTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify handle_role_change deletes meta when role changes to a real role.
	 *
	 * @return void
	 */
	public function test_handle_role_change_deletes_meta_on_promotion(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 42, '_agency_pass_user', true )
			->andReturn( '1' );

		Functions\expect( 'delete_user_meta' )
			->once()
			->with( 42, '_agency_pass_user' );

		Functions\expect( 'delete_user_meta' )
			->once()
			->with( 42, '_agency_pass_expires' );

		UserProfile::handle_role_change( 42, 'editor', [ Role::ROLE_NAME ] );
	}

	/**
	 * Verify handle_role_change preserves meta when role is revoked to empty.
	 *
	 * This happens on logout and expiry — the user should remain managed
	 * so they can re-request a magic link.
	 *
	 * @return void
	 */
	public function test_handle_role_change_preserves_meta_on_revocation(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 42, '_agency_pass_user', true )
			->andReturn( '1' );

		Functions\expect( 'delete_user_meta' )->never();

		UserProfile::handle_role_change( 42, '', [ Role::ROLE_NAME ] );
	}

	/**
	 * Verify handle_role_change skips non-managed users.
	 *
	 * @return void
	 */
	public function test_handle_role_change_ignores_non_managed_users(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 10, '_agency_pass_user', true )
			->andReturn( '' );

		Functions\expect( 'delete_user_meta' )->never();

		UserProfile::handle_role_change( 10, 'subscriber', [ 'editor' ] );
	}

	/**
	 * Verify handle_role_change skips when role stays agency_pass_admin.
	 *
	 * @return void
	 */
	public function test_handle_role_change_skips_same_role(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 42, '_agency_pass_user', true )
			->andReturn( '1' );

		Functions\expect( 'delete_user_meta' )->never();

		UserProfile::handle_role_change( 42, Role::ROLE_NAME, [ Role::ROLE_NAME ] );
	}
}
