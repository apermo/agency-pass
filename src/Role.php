<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Manages the custom agency_pass_admin role.
 */
class Role {

	public const ROLE_NAME = 'agency_pass_admin';

	private const REMOVED_CAPS = [
		'edit_users',
		'delete_users',
		'create_users',
		'promote_users',
		'remove_users',
	];

	/**
	 * Register hooks for capability filtering.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'map_meta_cap', [ self::class, 'block_self_edit' ], 10, 4 );
	}

	/**
	 * Prevent agency pass users from editing their own profile.
	 *
	 * @param string[] $caps    Primitive capabilities required.
	 * @param string   $cap     Meta capability being checked.
	 * @param int      $user_id User ID being checked.
	 * @param mixed[]  $args    Additional arguments.
	 *
	 * @return string[]
	 */
	public static function block_self_edit( array $caps, string $cap, int $user_id, array $args ): array {
		if ( $cap !== 'edit_user' ) {
			return $caps;
		}

		$user = get_userdata( $user_id );
		if ( $user === false || ! \in_array( self::ROLE_NAME, $user->roles, true ) ) {
			return $caps;
		}

		return [ 'do_not_allow' ];
	}

	/**
	 * Register the custom role by cloning administrator and removing user-management caps.
	 *
	 * @return void
	 */
	public static function register(): void {
		$admin = get_role( 'administrator' );
		if ( $admin === null ) {
			return;
		}

		$capabilitys = $admin->capabilities;
		foreach ( self::REMOVED_CAPS as $capability ) {
			unset( $capabilitys[ $capability ] );
		}

		add_role(
			self::ROLE_NAME,
			__( 'Agency Pass Admin', 'agency-pass' ),
			$capabilitys,
		);
	}

	/**
	 * Remove the custom role.
	 *
	 * @return void
	 */
	public static function unregister(): void {
		remove_role( self::ROLE_NAME );
	}

	/**
	 * Ensure the role exists at runtime.
	 *
	 * Handles the case where the plugin was updated but not re-activated.
	 *
	 * @return void
	 */
	public static function ensure_exists(): void {
		if ( get_role( self::ROLE_NAME ) === null ) {
			self::register();
		}
	}
}
