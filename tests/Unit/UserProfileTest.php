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
		unset( $_POST['agency_pass_promote'], $_POST['agency_pass_promote_nonce'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify register_hooks registers all expected hooks.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'edit_user_profile', [ UserProfile::class, 'render_status' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'set_user_role', [ UserProfile::class, 'handle_role_change' ], 10, 3 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_agency_pass_reenroll', [ UserProfile::class, 'handle_reenroll' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_agency_pass_end_session', [ UserProfile::class, 'handle_end_session' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_notices', [ UserProfile::class, 'render_admin_notice' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_footer', [ UserProfile::class, 'render_role_confirm_script' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'edit_user_profile_update', [ UserProfile::class, 'handle_promote' ] );

		UserProfile::register_hooks();
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

	/**
	 * Verify handle_promote deletes meta when hidden input and nonce are valid.
	 *
	 * @return void
	 */
	public function test_handle_promote_deletes_meta_with_valid_nonce(): void {
		$_POST['agency_pass_promote']       = '1';
		$_POST['agency_pass_promote_nonce'] = 'valid-nonce';

		Functions\stubs(
			[
				'sanitize_text_field' => static fn( $val ) => $val,
				'wp_unslash'          => static fn( $val ) => $val,
			],
		);

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'valid-nonce', 'agency_pass_promote_42' )
			->andReturn( 1 );

		Functions\expect( 'delete_user_meta' )
			->once()
			->with( 42, '_agency_pass_user' );

		Functions\expect( 'delete_user_meta' )
			->once()
			->with( 42, '_agency_pass_expires' );

		UserProfile::handle_promote( 42 );
	}

	/**
	 * Verify handle_promote skips when hidden input is absent.
	 *
	 * @return void
	 */
	public function test_handle_promote_skips_without_hidden_input(): void {
		Functions\expect( 'delete_user_meta' )->never();

		UserProfile::handle_promote( 42 );
	}

	/**
	 * Verify handle_promote skips when nonce is invalid.
	 *
	 * @return void
	 */
	public function test_handle_promote_skips_with_invalid_nonce(): void {
		$_POST['agency_pass_promote']       = '1';
		$_POST['agency_pass_promote_nonce'] = 'bad-nonce';

		Functions\stubs(
			[
				'sanitize_text_field' => static fn( $val ) => $val,
				'wp_unslash'          => static fn( $val ) => $val,
			],
		);

		Functions\expect( 'wp_verify_nonce' )
			->once()
			->with( 'bad-nonce', 'agency_pass_promote_42' )
			->andReturn( false );

		Functions\expect( 'delete_user_meta' )->never();

		UserProfile::handle_promote( 42 );
	}
}
