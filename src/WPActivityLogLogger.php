<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * WP Activity Log (Melapress) audit logger.
 *
 * Stub implementation — logs to error_log() for now.
 * Full WSAL sensor integration is tracked in a separate issue.
 */
class WPActivityLogLogger implements AuditLoggerInterface {

	/**
	 * Check whether WP Activity Log is installed and active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'WpSecurityAuditLog' );
	}

	/**
	 * Register hooks to capture Agency Pass events.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'agency_pass_link_requested', [ self::class, 'on_link_requested' ], 10, 3 );
		add_action( 'agency_pass_login', [ self::class, 'on_login' ], 10, 3 );
		add_action( 'agency_pass_user_cleanup', [ self::class, 'on_user_cleanup' ], 10, 1 );
	}

	/**
	 * Log a magic link request.
	 *
	 * @param string $email   The requesting email address.
	 * @param string $ip      The requesting IP address.
	 * @param bool   $matched Whether the email matched the allowed pattern.
	 *
	 * @return void
	 */
	public static function on_link_requested( string $email, string $ip, bool $matched ): void {
		// TODO: Fire WSAL custom alert once sensor integration is implemented.
		$status = $matched ? 'matched' : 'rejected';
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\sprintf(
				'[Agency Pass] Magic link requested: email=%s, ip=%s, status=%s',
				$email,
				$ip,
				$status,
			),
		);
	}

	/**
	 * Log an emergency login.
	 *
	 * @param string $email    The email address used.
	 * @param string $username The username created or reused.
	 * @param string $ip       The requesting IP address.
	 *
	 * @return void
	 */
	public static function on_login( string $email, string $username, string $ip ): void {
		// TODO: Fire WSAL custom alert once sensor integration is implemented.
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\sprintf(
				'[Agency Pass] Emergency login: email=%s, user=%s, ip=%s',
				$email,
				$username,
				$ip,
			),
		);
	}

	/**
	 * Log user cleanup.
	 *
	 * @param string $username The username that was cleaned up.
	 *
	 * @return void
	 */
	public static function on_user_cleanup( string $username ): void {
		// TODO: Fire WSAL custom alert once sensor integration is implemented.
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\sprintf(
				'[Agency Pass] User expired and cleaned up: user=%s',
				$username,
			),
		);
	}
}
