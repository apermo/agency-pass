<?php

declare(strict_types=1);

namespace Agency_Pass\Tests\Unit;

use Agency_Pass\Cleanup;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Cleanup class.
 */
class CleanupTest extends TestCase {

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
	 * Verify schedule creates a cron event when none exists.
	 *
	 * @return void
	 */
	public function test_schedule_creates_event(): void {
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'agency_pass_cleanup' )
			->andReturn( false );

		Functions\expect( 'wp_schedule_event' )
			->once()
			->withArgs(
				static function ( int $time, string $recurrence, string $hook ): bool {
					return $recurrence === 'hourly' && $hook === 'agency_pass_cleanup';
				},
			);

		Cleanup::schedule();
	}

	/**
	 * Verify schedule does not duplicate if already scheduled.
	 *
	 * @return void
	 */
	public function test_schedule_skips_when_already_scheduled(): void {
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'agency_pass_cleanup' )
			->andReturn( 1234567890 );

		Functions\expect( 'wp_schedule_event' )->never();

		Cleanup::schedule();
	}

	/**
	 * Verify unschedule removes the cron event.
	 *
	 * @return void
	 */
	public function test_unschedule(): void {
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'agency_pass_cleanup' )
			->andReturn( 1234567890 );

		Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( 1234567890, 'agency_pass_cleanup' );

		Cleanup::unschedule();
	}

	/**
	 * Verify register_hooks registers the cron and init actions.
	 *
	 * @return void
	 */
	public function test_register_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'agency_pass_cleanup', [ Cleanup::class, 'run' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', [ Cleanup::class, 'maybe_run' ] );

		Cleanup::register_hooks();
	}

	/**
	 * Verify maybe_run skips when transient exists (throttled).
	 *
	 * @return void
	 */
	public function test_maybe_run_skips_when_throttled(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'agency_pass_last_cleanup' )
			->andReturn( '1' );

		Functions\expect( 'set_transient' )->never();
		Functions\expect( 'get_users' )->never();

		Cleanup::maybe_run();
	}
}
