<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Audit logger registry.
 *
 * Manages registered AuditLoggerInterface implementations and checks
 * whether at least one backing audit trail plugin is available.
 */
class AuditLog {

	/**
	 * Registered logger class names.
	 *
	 * @var list<class-string<AuditLoggerInterface>>
	 */
	private static array $loggers = [
		WPActivityLogLogger::class,
	];

	/**
	 * Check whether at least one registered logger's backing plugin is active.
	 *
	 * @return bool
	 */
	public static function has_available_logger(): bool {
		foreach ( self::$loggers as $logger ) {
			if ( $logger::is_available() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register hooks on all available loggers.
	 *
	 * Always logs to error_log() as a fallback regardless of logger availability.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		foreach ( self::$loggers as $logger ) {
			if ( $logger::is_available() ) {
				$logger::register_hooks();
			}
		}

		// Fallback: always log to error_log().
		add_action( 'agency_pass_link_requested', [ self::class, 'fallback_link_requested' ], 99, 3 );
		add_action( 'agency_pass_login', [ self::class, 'fallback_login' ], 99, 3 );
		add_action( 'agency_pass_user_cleanup', [ self::class, 'fallback_user_cleanup' ], 99, 1 );
	}

	/**
	 * Fallback: log magic link request to error_log.
	 *
	 * @param string $email   The requesting email address.
	 * @param string $ip      The requesting IP address.
	 * @param bool   $matched Whether the email matched the allowed pattern.
	 *
	 * @return void
	 */
	public static function fallback_link_requested( string $email, string $ip, bool $matched ): void {
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
	 * Fallback: log emergency login to error_log.
	 *
	 * @param string $email    The email address used.
	 * @param string $username The username created or reused.
	 * @param string $ip       The requesting IP address.
	 *
	 * @return void
	 */
	public static function fallback_login( string $email, string $username, string $ip ): void {
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
	 * Fallback: log user cleanup to error_log.
	 *
	 * @param string $username The username that was cleaned up.
	 *
	 * @return void
	 */
	public static function fallback_user_cleanup( string $username ): void {
		\error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			\sprintf(
				'[Agency Pass] User expired and cleaned up: user=%s',
				$username,
			),
		);
	}
}
