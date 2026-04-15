<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\AuditLog;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AuditLog registry class.
 */
class AuditLogTest extends TestCase {

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
	 * Verify has_available_logger returns false when no logger is available.
	 *
	 * WpSecurityAuditLog class does not exist in tests.
	 *
	 * @return void
	 */
	public function test_has_available_logger_returns_false(): void {
		$this->assertFalse( AuditLog::has_available_logger() );
	}

	/**
	 * Verify register_hooks always registers fallback handlers.
	 *
	 * @return void
	 */
	public function test_register_hooks_registers_fallbacks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_link_requested', [ AuditLog::class, 'fallback_link_requested' ], 99, 3 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_login', [ AuditLog::class, 'fallback_login' ], 99, 3 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_user_cleanup', [ AuditLog::class, 'fallback_user_cleanup' ], 99, 1 );

		AuditLog::register_hooks();
	}

	/**
	 * Verify fallback_link_requested completes without error.
	 *
	 * @return void
	 */
	public function test_fallback_link_requested(): void {
		AuditLog::fallback_link_requested( 'test@example.tld', '127.0.0.1', true );
		$this->assertTrue( true );
	}

	/**
	 * Verify fallback_login completes without error.
	 *
	 * @return void
	 */
	public function test_fallback_login(): void {
		AuditLog::fallback_login( 'test@example.tld', 'agencypass-test', '127.0.0.1' );
		$this->assertTrue( true );
	}

	/**
	 * Verify fallback_user_cleanup completes without error.
	 *
	 * @return void
	 */
	public function test_fallback_user_cleanup(): void {
		AuditLog::fallback_user_cleanup( 'agencypass-test' );
		$this->assertTrue( true );
	}
}
