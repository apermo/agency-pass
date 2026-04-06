<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Manages temporary emergency user lifecycle.
 */
class UserManager {

	private const META_EXPIRES = '_agency_pass_expires';
	private const META_MARKER  = '_agency_pass_user';
	private const DEFAULT_TTL  = 28800; // 8 hours (one work day).

	/**
	 * Creates or reuses a temporary emergency user for the given email.
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
	 * Finds an existing non-expired emergency user by email.
	 *
	 * If an expired user is found, revoke its role instead of deleting it.
	 *
	 * @param string $email The email to search for.
	 *
	 * @return int|null User ID or null if none found.
	 */
	public static function find_existing( string $email ): ?int {
		$users = get_users(
			[
				'search'         => $email,
				'search_columns' => [ 'user_email' ],
				'meta_key'       => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'         => 1,
				'fields'         => 'ID',
			],
		);

		if ( $users === [] ) {
			return null;
		}

		$user_id = (int) $users[0];
		$expires = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

		if ( $expires < \time() ) {
			self::revoke_role( $user_id );
			return null;
		}

		return $user_id;
	}

	/**
	 * Creates a new temporary emergency user.
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
	 * Extends the TTL of an existing emergency user.
	 *
	 * @param int $user_id The user ID to extend.
	 *
	 * @return void
	 */
	public static function extend( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( $user !== false && ! \in_array( Role::ROLE_NAME, $user->roles, true ) ) {
			$user->set_role( Role::ROLE_NAME );
		}

		update_user_meta( $user_id, self::META_EXPIRES, \time() + self::ttl() );
	}

	/**
	 * Revokes the role of all expired emergency users.
	 *
	 * @return void
	 */
	public static function expire_users(): void {
		$users = get_users(
			[
				'meta_key'   => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'role'       => Role::ROLE_NAME,
				'fields'     => 'ID',
			],
		);

		foreach ( $users as $user_id ) {
			$user_id = (int) $user_id;
			$expires = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

			if ( $expires < \time() ) {
				self::revoke_role( $user_id );

				$user = get_userdata( $user_id );
				if ( $user !== false ) {
					/**
					 * Fires when an emergency user's role is revoked after expiry.
					 *
					 * @param string $username The username that was expired.
					 */
					do_action( 'agency_pass_user_cleanup', $user->user_login ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				}
			}
		}
	}

	/**
	 * Revokes the role of all emergency users (for deactivation).
	 *
	 * @return void
	 */
	public static function revoke_all(): void {
		$users = get_users(
			[
				'meta_key'   => self::META_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			],
		);

		foreach ( $users as $user_id ) {
			self::revoke_role( (int) $user_id );
		}
	}

	/**
	 * Removes the agency pass role from a user, leaving them with no role.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return void
	 */
	private static function revoke_role( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( $user !== false ) {
			$user->set_role( '' );
		}
	}

	/**
	 * Generates a username from an email address.
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
	 * Returns the configured user TTL in seconds.
	 *
	 * @return int
	 */
	public static function ttl(): int {
		if ( \defined( 'AGENCY_PASS_USER_TTL' ) ) {
			return (int) \AGENCY_PASS_USER_TTL;
		}

		return self::DEFAULT_TTL;
	}
}
