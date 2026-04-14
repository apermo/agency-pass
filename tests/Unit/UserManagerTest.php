<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\UserManager;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the UserManager class.
 */
class UserManagerTest extends TestCase {

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
	 * Verify find_existing returns null when no managed user exists.
	 *
	 * @return void
	 */
	public function test_find_existing_returns_null_when_no_user(): void {
		Functions\expect( 'get_users' )
			->once()
			->andReturn( [] );

		$this->assertNull( UserManager::find_existing( 'nobody@example.tld' ) );
	}

	/**
	 * Verify find_existing returns the user ID for a non-expired user.
	 *
	 * @return void
	 */
	public function test_find_existing_returns_active_user(): void {
		Functions\expect( 'get_users' )
			->once()
			->andReturn( [ 42 ] );

		$this->assertSame( 42, UserManager::find_existing( 'sabrina@example.tld' ) );
	}

	/**
	 * Verify find_existing returns expired users for reactivation.
	 *
	 * Expired managed users must be returned so extend() can reactivate
	 * them instead of create() failing on duplicate email.
	 *
	 * @return void
	 */
	public function test_find_existing_returns_expired_user(): void {
		Functions\expect( 'get_users' )
			->once()
			->andReturn( [ 99 ] );

		$this->assertSame( 99, UserManager::find_existing( 'expired@example.tld' ) );
	}

	/**
	 * Verify the default TTL is 8 hours.
	 *
	 * @return void
	 */
	public function test_default_ttl(): void {
		$this->assertSame( 28800, UserManager::ttl() );
	}
}
