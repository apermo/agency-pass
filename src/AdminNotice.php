<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Displays admin notices for missing dependencies.
 */
class AdminNotice {

	/**
	 * Register the admin notice hook.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_notices', [ self::class, 'missing_audit_logger' ] );
	}

	/**
	 * Show a warning when no audit trail plugin is active.
	 *
	 * @return void
	 */
	public static function missing_audit_logger(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Agency Pass:', 'agency-pass' ); ?></strong>
				<?php
				esc_html_e(
					'An audit trail plugin (e.g. WP Activity Log) is required. Emergency login is disabled until one is installed and activated.',
					'agency-pass',
				);
				?>
			</p>
		</div>
		<?php
	}
}
