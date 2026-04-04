<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Audit logging for Agency Pass events.
 *
 * Hooks into custom actions and logs to error_log() as a fallback.
 */
class AuditLog {

	/**
	 * Register audit logging hooks.
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
	 * @param string $ip    The requesting IP address.
	 * @param bool   $matched Whether the email matched the allowed pattern.
	 *
	 * @return void
	 */
	public static function on_link_requested( string $email, string $ip, bool $matched ): void {
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
	 * @param string $ip     The requesting IP address.
	 *
	 * @return void
	 */
	public static function on_login( string $email, string $username, string $ip ): void {
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
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\sprintf(
				'[Agency Pass] User expired and cleaned up: user=%s',
				$username,
			),
		);
	}
}
