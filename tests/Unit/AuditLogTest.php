<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\AuditLog;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AuditLog class.
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
	 * Verify register_hooks registers the expected actions.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_link_requested', [ AuditLog::class, 'on_link_requested' ], 10, 3 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_login', [ AuditLog::class, 'on_login' ], 10, 3 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_user_cleanup', [ AuditLog::class, 'on_user_cleanup' ], 10, 1 );

		AuditLog::register_hooks();
	}

	/**
	 * Verify on_link_requested completes without error.
	 *
	 * @return void
	 */
	public function test_on_link_requested_logs(): void {
		AuditLog::on_link_requested( 'test@example.tld', '127.0.0.1', true );
		$this->assertTrue( true );
	}

	/**
	 * Verify on_login completes without error.
	 *
	 * @return void
	 */
	public function test_on_login_logs(): void {
		AuditLog::on_login( 'test@example.tld', 'agencypass-test', '127.0.0.1' );
		$this->assertTrue( true );
	}

	/**
	 * Verify on_user_cleanup completes without error.
	 *
	 * @return void
	 */
	public function test_on_user_cleanup_logs(): void {
		AuditLog::on_user_cleanup( 'agencypass-test' );
		$this->assertTrue( true );
	}
}
