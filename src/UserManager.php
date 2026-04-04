<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Manages temporary emergency user lifecycle.
 */
class UserManager {

	private const META_EXPIRES = '_agency_pass_expires';
	private const META_MARKER  = '_agency_pass_user';

	/**
	 * Create or reuse a temporary emergency user for the given email.
	 *
	 * If an active (non-expired) user already exists for this email, reuse it
	 * and extend the TTL.
	 *
	 * @param string $email The requesting user's email address.
	 *
	 * @return int The user ID.
	 */
	public static function create_or_reuse( string $email ): int {
		$existing = self::find_existing( $email );

		if ( $existing !== null ) {
			self::extend( $existing );
			return $existing;
		}

		return self::create( $email );
	}

	/**
	 * Find an existing non-expired emergency user by email.
	 *
	 * @param string $email The email to search for.
	 *
	 * @return int|null User ID or null if none found.
	 */
	public static function find_existing( string $email ): ?int {
		$users = get_users(
			[
				'email'      => $email,
				'meta_key'   => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			],
		);

		if ( $users === [] ) {
			return null;
		}

		$user_id = (int) $users[0];
		$expires = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

		if ( $expires < \time() ) {
			wp_delete_user( $user_id );
			return null;
		}

		return $user_id;
	}

	/**
	 * Create a new temporary emergency user.
	 *
	 * @param string $email The requesting user's email address.
	 *
	 * @return int The new user ID.
	 */
	public static function create( string $email ): int {
		$username = self::generate_username( $email );

		$user_id = wp_insert_user(
			[
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 64, true, true ),
				'role'       => Role::ROLE_NAME,
			],
		);

		if ( is_wp_error( $user_id ) ) {
			// Username collision: append random suffix and retry.
			$username = $username . '-' . \bin2hex( \random_bytes( 3 ) );
			$user_id  = wp_insert_user(
				[
					'user_login' => $username,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 64, true, true ),
					'role'       => Role::ROLE_NAME,
				],
			);
		}

		update_user_meta( $user_id, self::META_MARKER, '1' );
		update_user_meta( $user_id, self::META_EXPIRES, \time() + self::ttl() );

		return $user_id;
	}

	/**
	 * Extend the TTL of an existing emergency user.
	 *
	 * @param int $user_id The user ID to extend.
	 *
	 * @return void
	 */
	public static function extend( int $user_id ): void {
		update_user_meta( $user_id, self::META_EXPIRES, \time() + self::ttl() );
	}

	/**
	 * Delete all expired emergency users.
	 *
	 * @return void
	 */
	public static function delete_expired(): void {
		$users = get_users(
			[
				'meta_key'   => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			],
		);

		foreach ( $users as $user_id ) {
			$user_id = (int) $user_id;
			$expires = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

			if ( $expires < \time() ) {
				$user = get_userdata( $user_id );
				wp_delete_user( $user_id );

				if ( $user !== false ) {
					/**
					 * Fires when an expired emergency user is cleaned up.
					 *
					 * @param string $username The username that was cleaned up.
					 */
					do_action( 'agency_pass_user_cleanup', $user->user_login ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				}
			}
		}
	}

	/**
	 * Delete all emergency users (for uninstall/deactivation).
	 *
	 * @return void
	 */
	public static function delete_all(): void {
		$users = get_users(
			[
				'meta_key'   => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			],
		);

		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id );
		}
	}

	/**
	 * Generate a username from an email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return string The generated username (e.g. "agencypass-christoph").
	 */
	private static function generate_username( string $email ): string {
		$local_part = \strstr( $email, '@', true );

		if ( $local_part === false ) {
			$local_part = $email;
		}

		return 'agencypass-' . sanitize_user( $local_part, true );
	}

	/**
	 * Return the configured user TTL in seconds.
	 *
	 * @return int
	 */
	private static function ttl(): int {
		if ( \defined( 'AGENCY_PASS_USER_TTL' ) ) {
			return (int) \AGENCY_PASS_USER_TTL;
		}

		return 86400;
	}
}
