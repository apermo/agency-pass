<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\Plugin;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase {

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
	 * Verify init registers activation and deactivation hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_hooks(): void {
		$file = '/tmp/plugin.php';

		Functions\expect( 'register_activation_hook' )
			->once()
			->with( $file, [ Plugin::class, 'activate' ] );

		Functions\expect( 'register_deactivation_hook' )
			->once()
			->with( $file, [ Plugin::class, 'deactivate' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'plugins_loaded', [ Plugin::class, 'boot' ] );

		Plugin::init( $file );
	}

	/**
	 * Verify init stores the plugin file path.
	 *
	 * @return void
	 */
	public function test_init_stores_file_path(): void {
		$file = '/tmp/plugin.php';

		Functions\stubs(
			[
				'register_activation_hook',
				'register_deactivation_hook',
				'add_action',
			],
		);

		Plugin::init( $file );

		$this->assertSame( $file, Plugin::file() );
	}

	/**
	 * Verify boot does nothing when AGENCY_PASS_EMAIL_PATTERN is not defined.
	 *
	 * @return void
	 */
	public function test_boot_bails_without_email_pattern(): void {
		// AGENCY_PASS_EMAIL_PATTERN is not defined in tests, so boot should return early.
		Functions\expect( 'add_action' )->never();

		Plugin::boot();
	}
}
