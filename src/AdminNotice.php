<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Displays admin notices for missing dependencies.
 */
class AdminNotice {

	/**
	 * Registers the admin notice hook.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_notices', [ self::class, 'missing_audit_logger' ] );
	}

	/**
	 * Shows a warning when no audit trail plugin is active.
	 *
	 * @return void
	 */
	public static function missing_audit_logger(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_admin_notice(
			\sprintf(
				'<strong>%s</strong> %s',
				esc_html__( 'Agency Pass:', 'agency-pass' ),
				esc_html__(
					'An audit trail plugin (e.g. WP Activity Log) is required. Emergency login is disabled until one is installed and activated.',
					'agency-pass',
				),
			),
			[
				'type'           => 'warning',
				'paragraph_wrap' => true,
			],
		);
	}
}
