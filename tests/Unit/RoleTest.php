<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

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

		Functions\expect( '__' )
			->once()
			->andReturnFirstArg();

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
		$this->assertArrayNotHasKey( 'list_users', $captured_caps );
		$this->assertArrayNotHasKey( 'promote_users', $captured_caps );
		$this->assertArrayNotHasKey( 'remove_users', $captured_caps );
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
	 * Verify ensure_exists registers the role if missing.
	 *
	 * @return void
	 */
	public function test_ensure_exists_registers_when_missing(): void {
		Functions\expect( 'get_role' )
			->once()
			->with( 'agency_pass_admin' )
			->andReturn( null );

		// register() will be called, which calls get_role('administrator').
		Functions\expect( 'get_role' )
			->once()
			->with( 'administrator' )
			->andReturn( null );

		Role::ensure_exists();
	}

	/**
	 * Verify ensure_exists does nothing if role already exists.
	 *
	 * @return void
	 */
	public function test_ensure_exists_skips_when_present(): void {
		$role = new stdClass();

		Functions\expect( 'get_role' )
			->once()
			->with( 'agency_pass_admin' )
			->andReturn( $role );

		Functions\expect( 'add_role' )->never();

		Role::ensure_exists();
	}
}
