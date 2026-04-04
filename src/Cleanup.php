<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Manages WP-Cron based cleanup of expired emergency users.
 */
class Cleanup {

	public const CRON_HOOK = 'agency_pass_cleanup';

	/**
	 * Schedule the hourly cleanup cron event.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( \time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cleanup cron event.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Register hooks for cleanup.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( self::CRON_HOOK, [ self::class, 'run' ] );

		// Safety net: also run on init in case cron is unreliable.
		add_action( 'init', [ self::class, 'maybe_run' ] );
	}

	/**
	 * Run the cleanup.
	 *
	 * @return void
	 */
	public static function run(): void {
		UserManager::delete_expired();
	}

	/**
	 * Run cleanup on init if cron hasn't fired recently.
	 *
	 * Uses a transient to throttle init-based cleanup to once per hour.
	 *
	 * @return void
	 */
	public static function maybe_run(): void {
		if ( get_transient( 'agency_pass_last_cleanup' ) !== false ) {
			return;
		}

		set_transient( 'agency_pass_last_cleanup', '1', \HOUR_IN_SECONDS );
		self::run();
	}
}
