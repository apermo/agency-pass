<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\RequestHandler;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the RequestHandler class.
 */
class RequestHandlerTest extends TestCase {

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
	 * Verify register_hooks registers the password reset filter.
	 *
	 * @return void
	 */
	public function test_register_hooks_includes_password_reset_filter(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_nopriv_agency_pass_request', [ RequestHandler::class, 'handle_request' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_post_nopriv_agency_pass_login', [ RequestHandler::class, 'handle_login' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_logout', [ RequestHandler::class, 'maybe_revoke_on_logout' ], 10, 1 );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'allow_password_reset', [ RequestHandler::class, 'block_password_reset' ], 10, 2 );

		RequestHandler::register_hooks();
	}

	/**
	 * Verify block_password_reset returns false for managed users.
	 *
	 * @return void
	 */
	public function test_block_password_reset_denies_managed_user(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 42, '_agency_pass_user', true )
			->andReturn( '1' );

		$this->assertFalse( RequestHandler::block_password_reset( true, 42 ) );
	}

	/**
	 * Verify block_password_reset passes through for regular users.
	 *
	 * @return void
	 */
	public function test_block_password_reset_allows_regular_user(): void {
		Functions\expect( 'get_user_meta' )
			->once()
			->with( 10, '_agency_pass_user', true )
			->andReturn( '' );

		$this->assertTrue( RequestHandler::block_password_reset( true, 10 ) );
	}
}
