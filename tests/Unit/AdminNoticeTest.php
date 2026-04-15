<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\AdminNotice;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AdminNotice class.
 */
class AdminNoticeTest extends TestCase {

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
	 * Verify register_hooks registers the admin_notices action.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_notices', [ AdminNotice::class, 'missing_audit_logger' ] );

		AdminNotice::register_hooks();
	}

	/**
	 * Verify notice is not shown to users without manage_options.
	 *
	 * @return void
	 */
	public function test_notice_hidden_without_capability(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		Functions\expect( 'wp_admin_notice' )->never();

		AdminNotice::missing_audit_logger();
	}

	/**
	 * Verify notice is shown to admins via wp_admin_notice.
	 *
	 * @return void
	 */
	public function test_notice_shown_to_admins(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		Functions\stubs(
			[
				'esc_html__' => static fn( string $text ): string => $text,
			],
		);

		Functions\expect( 'wp_admin_notice' )
			->once()
			->withArgs(
				static function ( string $message, array $args ): bool {
					return \str_contains( $message, 'audit trail plugin' )
						&& $args['type'] === 'warning';
				},
			);

		AdminNotice::missing_audit_logger();
	}
}
