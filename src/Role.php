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
		'list_users',
		'promote_users',
		'remove_users',
	];

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
