<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Handles magic-link token generation, storage, and validation.
 */
class Token {

	private const TRANSIENT_PREFIX = 'agency_pass_token_';

	/**
	 * Generates a new magic-link token for the given email.
	 *
	 * @param string $email   Requesting user's email address.
	 * @param string $ip Requesting user's IP address.
	 *
	 * @return string The generated token.
	 */
	public static function generate( string $email, string $ip ): string {
		$token = \bin2hex( \random_bytes( 32 ) );

		set_transient(
			self::TRANSIENT_PREFIX . $token,
			[
				'email'      => $email,
				'created_at' => \time(),
				'ip'         => $ip,
			],
			self::ttl(),
		);

		return $token;
	}

	/**
	 * Validates and consumes a token.
	 *
	 * Tokens are single-use — deleted immediately after successful validation.
	 *
	 * @param string $token The token to validate.
	 *
	 * @return array{email: string, created_at: int, ip: string}|null Token data or null if invalid/expired.
	 */
	public static function validate( string $token ): ?array {
		$key  = self::TRANSIENT_PREFIX . $token;
		$data = get_transient( $key );

		if ( $data === false || ! \is_array( $data ) ) {
			return null;
		}

		// Single-use: delete immediately.
		delete_transient( $key );

		return $data;
	}

	/**
	 * Returns the configured token TTL in seconds.
	 *
	 * @return int
	 */
	public static function ttl(): int {
		if ( \defined( 'AGENCY_PASS_TOKEN_TTL' ) ) {
			return (int) \AGENCY_PASS_TOKEN_TTL;
		}

		return 900;
	}
}
