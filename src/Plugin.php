<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Main plugin class.
 */
class Plugin {

	public const VERSION = '0.1.0';

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private static string $file = '';

	/**
	 * Initializes the plugin.
	 *
	 * @param string $file Main plugin file path.
	 *
	 * @return void
	 */
	public static function init( string $file ): void {
		self::$file = $file;

		register_activation_hook( $file, [ self::class, 'activate' ] );
		register_deactivation_hook( $file, [ self::class, 'deactivate' ] );
		add_action( 'plugins_loaded', [ self::class, 'boot' ] );
	}

	/**
	 * Returns the main plugin file path.
	 *
	 * @return string
	 */
	public static function file(): string {
		return self::$file;
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Role::register();
		MuPluginInstaller::install();
		Cleanup::schedule();
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		MuPluginInstaller::uninstall();
		Role::unregister();
		Cleanup::unschedule();
		UserManager::revoke_all();
	}

	/**
	 * Boots the plugin after all plugins are loaded.
	 *
	 * Fail closed: if AGENCY_PASS_EMAIL_PATTERN is not defined, do nothing.
	 *
	 * @return void
	 */
	public static function boot(): void {
		if ( ! \defined( 'AGENCY_PASS_EMAIL_PATTERN' ) ) {
			return;
		}

		Role::ensure_exists();
		Role::register_hooks();
		UserProfile::register_hooks();

		if ( ! AuditLog::has_available_logger() ) {
			AdminNotice::register_hooks();
			return;
		}

		AuditLog::register_hooks();
		LoginForm::register_hooks();
		RequestHandler::register_hooks();
		Cleanup::register_hooks();
	}
}
