<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\LoginForm;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LoginForm class.
 */
class LoginFormTest extends TestCase {

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
	 * Verify register_hooks registers the expected actions and filters.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'login_footer', [ LoginForm::class, 'render' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'login_enqueue_scripts', [ LoginForm::class, 'enqueue_styles' ] );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'login_message', [ LoginForm::class, 'confirmation_message' ] );

		LoginForm::register_hooks();
	}

	/**
	 * Verify confirmation_message returns original message when no query param.
	 *
	 * @return void
	 */
	public function test_confirmation_message_passthrough(): void {
		unset( $_GET['agency_pass_sent'] );

		$this->assertSame( 'Original', LoginForm::confirmation_message( 'Original' ) );
	}

	/**
	 * Verify confirmation_message returns confirmation when query param is set.
	 *
	 * @return void
	 */
	public function test_confirmation_message_shows_notice(): void {
		$_GET['agency_pass_sent'] = '1';

		Functions\stubs( [ 'esc_html__' => 'If your email is authorized, you will receive a login link shortly.' ] );

		$result = LoginForm::confirmation_message( 'Original' );

		$this->assertStringContainsString( 'If your email is authorized', $result );
		$this->assertStringContainsString( '<p class="message">', $result );

		unset( $_GET['agency_pass_sent'] );
	}
}
