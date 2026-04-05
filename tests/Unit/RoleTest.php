<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\Plugin;
use Agency_Pass\Role;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for the Role class.
 */
class RoleTest extends TestCase {

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
	 * Verify the role name constant.
	 *
	 * @return void
	 */
	public function test_role_name_constant(): void {
		$this->assertSame( 'agency_pass_admin', Role::ROLE_NAME );
	}

	/**
	 * Verify register_hooks registers capability filters.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'map_meta_cap', [ Role::class, 'block_self_edit' ], 10, 4 );

		Role::register_hooks();
	}

	/**
	 * Verify block_self_edit denies edit_user for agency pass users.
	 *
	 * @return void
	 */
	public function test_block_self_edit_denies_agency_pass_user(): void {
		$user        = new stdClass();
		$user->roles = [ 'agency_pass_admin' ];

		Functions\expect( 'get_userdata' )
			->once()
			->with( 42 )
			->andReturn( $user );

		$result = Role::block_self_edit( [ 'edit_users' ], 'edit_user', 42, [ 42 ] );

		$this->assertSame( [ 'do_not_allow' ], $result );
	}

	/**
	 * Verify block_self_edit passes through for non-agency-pass users.
	 *
	 * @return void
	 */
	public function test_block_self_edit_allows_regular_user(): void {
		$user        = new stdClass();
		$user->roles = [ 'administrator' ];

		Functions\expect( 'get_userdata' )
			->once()
			->with( 1 )
			->andReturn( $user );

		$result = Role::block_self_edit( [ 'edit_users' ], 'edit_user', 1, [ 1 ] );

		$this->assertSame( [ 'edit_users' ], $result );
	}

	/**
	 * Verify block_self_edit ignores non-edit_user caps.
	 *
	 * @return void
	 */
	public function test_block_self_edit_ignores_other_caps(): void {
		$result = Role::block_self_edit( [ 'manage_options' ], 'manage_options', 42, [] );

		$this->assertSame( [ 'manage_options' ], $result );
	}

	/**
	 * Verify register creates a role without user management caps.
	 *
	 * @return void
	 */
	public function test_register_creates_role_without_user_caps(): void {
		$admin_role               = new stdClass();
		$admin_role->capabilities = [
			'manage_options' => true,
			'edit_users'     => true,
			'delete_users'   => true,
			'create_users'   => true,
			'list_users'     => true,
			'promote_users'  => true,
			'remove_users'   => true,
			'edit_posts'     => true,
		];

		Functions\expect( 'get_role' )
			->once()
			->with( 'administrator' )
			->andReturn( $admin_role );

		$captured_caps = null;

		Functions\expect( 'add_role' )
			->once()
			->andReturnUsing(
				static function ( string $role, string $display, array $caps ) use ( &$captured_caps ): void {
					$captured_caps = $caps;
				},
			);

		Role::register();

		$this->assertIsArray( $captured_caps );
		$this->assertArrayNotHasKey( 'edit_users', $captured_caps );
		$this->assertArrayNotHasKey( 'delete_users', $captured_caps );
		$this->assertArrayNotHasKey( 'create_users', $captured_caps );
		$this->assertArrayNotHasKey( 'promote_users', $captured_caps );
		$this->assertArrayNotHasKey( 'remove_users', $captured_caps );
		$this->assertArrayHasKey( 'list_users', $captured_caps );
		$this->assertArrayHasKey( 'manage_options', $captured_caps );
		$this->assertArrayHasKey( 'edit_posts', $captured_caps );
	}

	/**
	 * Verify register does nothing if administrator role is missing.
	 *
	 * @return void
	 */
	public function test_register_bails_without_administrator(): void {
		Functions\expect( 'get_role' )
			->once()
			->with( 'administrator' )
			->andReturn( null );

		Functions\expect( 'add_role' )->never();

		Role::register();
	}

	/**
	 * Verify unregister removes the role.
	 *
	 * @return void
	 */
	public function test_unregister(): void {
		Functions\expect( 'remove_role' )
			->once()
			->with( 'agency_pass_admin' );

		Role::unregister();
	}

	/**
	 * Verify ensure_exists re-registers when version changes.
	 *
	 * @return void
	 */
	public function test_ensure_exists_re_registers_on_version_change(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'agency_pass_role_version', '' )
			->andReturn( '0.0.0' );

		Functions\expect( 'remove_role' )
			->once()
			->with( 'agency_pass_admin' );

		// The register method will query the administrator role.
		Functions\expect( 'get_role' )
			->once()
			->with( 'administrator' )
			->andReturn( null );

		Functions\expect( 'update_option' )
			->once();

		Role::ensure_exists();
	}

	/**
	 * Verify ensure_exists skips when version matches and role exists.
	 *
	 * @return void
	 */
	public function test_ensure_exists_skips_when_current(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'agency_pass_role_version', '' )
			->andReturn( Plugin::VERSION );

		$role = new stdClass();

		Functions\expect( 'get_role' )
			->once()
			->with( 'agency_pass_admin' )
			->andReturn( $role );

		Functions\expect( 'remove_role' )->never();

		Role::ensure_exists();
	}
}
