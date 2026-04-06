<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * WP Activity Log (Melapress) audit logger.
 *
 * Stub implementation — currently a no-op.
 * Full WSAL sensor integration is tracked in a separate issue.
 * The AuditLog fallback handles error_log() in the meantime.
 */
class WPActivityLogLogger implements AuditLoggerInterface {

	/**
	 * Checks whether WP Activity Log is installed and active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'WpSecurityAuditLog' );
	}

	/**
	 * Registers hooks to capture Agency Pass events.
	 *
	 * No-op until WSAL sensor integration is implemented.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		// Will register WSAL sensor hooks once implemented.
	}
}
