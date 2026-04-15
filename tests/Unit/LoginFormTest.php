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
		unset( $_GET['agency_pass'] );
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

		Functions\expect( 'add_action' )
			->once()
			->with( 'login_footer', [ LoginForm::class, 'maybe_shake' ], 12 );

		LoginForm::register_hooks();
	}

	/**
	 * Verify confirmation_message returns original message when no query param.
	 *
	 * @return void
	 */
	public function test_confirmation_message_passthrough(): void {
		$this->assertSame( 'Original', LoginForm::confirmation_message( 'Original' ) );
	}

	/**
	 * Verify confirmation_message returns generic notice on success.
	 *
	 * @return void
	 */
	public function test_confirmation_message_shows_sent_notice(): void {
		$_GET['agency_pass'] = 'sent';

		Functions\stubs(
			[
				'sanitize_text_field' => static fn( $val ) => $val,
				'wp_unslash'          => static fn( $val ) => $val,
				'esc_html__'          => static fn( $text ) => $text,
			],
		);

		$result = LoginForm::confirmation_message( 'Original' );

		$this->assertStringContainsString( 'If your email is authorized', $result );
		$this->assertStringContainsString( '<p class="message">', $result );
	}

	/**
	 * Verify confirmation_message returns error on rejection.
	 *
	 * @return void
	 */
	public function test_confirmation_message_shows_rejection_error(): void {
		$_GET['agency_pass'] = 'rejected';

		Functions\stubs(
			[
				'sanitize_text_field' => static fn( $val ) => $val,
				'wp_unslash'          => static fn( $val ) => $val,
				'esc_html__'          => static fn( $text ) => $text,
			],
		);

		$result = LoginForm::confirmation_message( 'Original' );

		$this->assertStringContainsString( 'not accepted', $result );
		$this->assertStringContainsString( 'login_error', $result );
		$this->assertStringContainsString( '<strong>', $result );
	}
}
