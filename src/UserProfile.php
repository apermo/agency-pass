<?php

declare(strict_types=1);

namespace Agency_Pass;

use WP_Session_Tokens;
use WP_User;

/**
 * Manages the Agency Pass status display on user profile pages.
 */
class UserProfile {

	/**
	 * Registers hooks for the user profile UI.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'edit_user_profile', [ self::class, 'render_status' ] );
		add_action( 'set_user_role', [ self::class, 'handle_role_change' ], 10, 3 );
		add_action( 'admin_post_agency_pass_reenroll', [ self::class, 'handle_reenroll' ] );
		add_action( 'admin_post_agency_pass_end_session', [ self::class, 'handle_end_session' ] );
		add_action( 'admin_notices', [ self::class, 'render_admin_notice' ] );
		add_action( 'admin_footer', [ self::class, 'render_role_confirm_script' ] );
		add_action( 'edit_user_profile_update', [ self::class, 'handle_promote' ] );
	}

	/**
	 * Renders the Agency Pass status section on a user's profile.
	 *
	 * @param WP_User $user The user being edited.
	 *
	 * @return void
	 */
	public static function render_status( WP_User $user ): void {
		$is_managed = get_user_meta( $user->ID, '_agency_pass_user', true ) === '1';

		if ( $is_managed ) {
			self::render_managed_status( $user );
			return;
		}

		if ( self::is_eligible_for_enrollment( $user->user_email ) ) {
			self::render_reenroll_button( $user );
		}
	}

	/**
	 * Handles role changes — promotes user if role changed away from agency_pass_admin.
	 *
	 * Hooked to `set_user_role` which fires on every role change.
	 *
	 * @param int      $user_id   The user ID.
	 * @param string   $role      The new role.
	 * @param string[] $old_roles The old roles.
	 *
	 * @return void
	 */
	public static function handle_role_change( int $user_id, string $role, array $old_roles ): void {
		if ( (string) get_user_meta( $user_id, '_agency_pass_user', true ) === '' ) {
			return;
		}

		if ( $role === Role::ROLE_NAME || $role === '' ) {
			return;
		}

		// Role changed to a real role — promote to regular account.
		delete_user_meta( $user_id, '_agency_pass_user' );
		delete_user_meta( $user_id, '_agency_pass_expires' );
	}

	/**
	 * Handles the re-enroll action.
	 *
	 * @return void
	 */
	public static function handle_reenroll(): void {
		$user_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ?? '0' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified immediately after.

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ), 'agency_pass_reenroll_' . $user_id ) ) {
			wp_die(
				esc_html__( 'Invalid request.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		if ( ! self::current_user_is_eligible() ) {
			wp_die(
				esc_html__( 'You are not authorized to perform this action.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		$user = get_userdata( $user_id );
		if ( $user === false ) {
			wp_die(
				esc_html__( 'User not found.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 404 ],
			);
		}

		update_user_meta( $user_id, '_agency_pass_user', '1' );
		update_user_meta( $user_id, '_agency_pass_expires', \time() + UserManager::ttl() );
		$user->set_role( Role::ROLE_NAME );

		wp_safe_redirect( get_edit_user_link( $user_id ) );
		exit();
	}

	/**
	 * Handles the end session action.
	 *
	 * @return void
	 */
	public static function handle_end_session(): void {
		$user_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ?? '0' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified immediately after.

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ), 'agency_pass_end_session_' . $user_id ) ) {
			wp_die(
				esc_html__( 'Invalid request.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		if ( ! self::current_user_is_eligible() ) {
			wp_die(
				esc_html__( 'You are not authorized to perform this action.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		$user = get_userdata( $user_id );
		if ( $user !== false ) {
			$user->set_role( '' );
			WP_Session_Tokens::get_instance( $user_id )->destroy_all();
		}

		wp_safe_redirect( admin_url( 'users.php' ) );
		exit();
	}

	/**
	 * Renders an admin notice on profile pages for managed users.
	 *
	 * @return void
	 */
	public static function render_admin_notice(): void {
		$screen = get_current_screen();
		if ( $screen === null || $screen->id !== 'user-edit' ) {
			return;
		}

		$user_id = (int) sanitize_text_field( wp_unslash( $_GET['user_id'] ?? '0' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.
		if ( $user_id === 0 ) {
			return;
		}

		if ( get_user_meta( $user_id, '_agency_pass_user', true ) !== '1' ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Agency Pass:', 'agency-pass' ); ?></strong>
				<?php esc_html_e( 'This account is managed by Agency Pass. Changing the role will permanently remove Agency Pass management.', 'agency-pass' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders the JS confirmation dialog for role changes on managed users.
	 *
	 * @return void
	 */
	public static function render_role_confirm_script(): void {
		$screen = get_current_screen();
		if ( $screen === null || $screen->id !== 'user-edit' ) {
			return;
		}

		$user_id = (int) sanitize_text_field( wp_unslash( $_GET['user_id'] ?? '0' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.
		if ( $user_id === 0 ) {
			return;
		}

		if ( get_user_meta( $user_id, '_agency_pass_user', true ) !== '1' ) {
			return;
		}

		$message = esc_js(
			__( 'This will permanently remove Agency Pass management from this user. Continue?', 'agency-pass' ),
		);
		?>
		<script>
		(function() {
			var roleSelect = document.getElementById('role');
			if (!roleSelect) { return; }

			var originalRole = roleSelect.value;

			roleSelect.addEventListener('change', function() {
				var input = document.getElementById('agency-pass-promote');

				if (this.value === originalRole) {
					if (input) { input.remove(); }
					return;
				}

				if (confirm('<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped via esc_js(). ?>')) {
					if (!input) {
						input = document.createElement('input');
						input.type = 'hidden';
						input.name = 'agency_pass_promote';
						input.id = 'agency-pass-promote';
						roleSelect.form.appendChild(input);
					}
					input.value = '1';
				} else {
					this.value = originalRole;
					if (input) { input.remove(); }
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Handles explicit promotion triggered by the hidden input.
	 *
	 * @param int $user_id The user ID being updated.
	 *
	 * @return void
	 */
	public static function handle_promote( int $user_id ): void {
		if ( ! isset( $_POST['agency_pass_promote'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['agency_pass_promote_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'agency_pass_promote_' . $user_id ) ) {
			return;
		}

		delete_user_meta( $user_id, '_agency_pass_user' );
		delete_user_meta( $user_id, '_agency_pass_expires' );
	}

	/**
	 * Renders the managed status info box with TTL and end-session button.
	 *
	 * @param WP_User $user The managed user.
	 *
	 * @return void
	 */
	private static function render_managed_status( WP_User $user ): void {
		$expires   = (int) get_user_meta( $user->ID, '_agency_pass_expires', true );
		$remaining = $expires - \time();
		$hours     = (int) \floor( $remaining / 3600 );
		$minutes   = (int) \ceil( ( $remaining % 3600 ) / 60 );

		if ( $remaining <= 0 ) {
			$time_label = esc_html__( 'expired', 'agency-pass' );
		} else {
			$time_label = \sprintf(
				/* translators: 1: hours, 2: minutes */
				esc_html__( '%1$dh %2$dmin remaining', 'agency-pass' ),
				$hours,
				$minutes,
			);
		}

		$end_session_url = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'agency_pass_end_session',
					'user_id' => $user->ID,
				],
				admin_url( 'admin-post.php' ),
			),
			'agency_pass_end_session_' . $user->ID,
		);
		?>
		<h2><?php esc_html_e( 'Agency Pass', 'agency-pass' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Status', 'agency-pass' ); ?></th>
				<td>
					<p>
						<?php esc_html_e( 'This account is managed by Agency Pass.', 'agency-pass' ); ?>
						<strong><?php echo esc_html( $time_label ); ?></strong>
					</p>
					<p class="description">
						<?php esc_html_e( 'Changing the role will remove Agency Pass management.', 'agency-pass' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( $end_session_url ); ?>" class="button button-secondary">
							<?php esc_html_e( 'End Session Now', 'agency-pass' ); ?>
						</a>
					</p>
				</td>
			</tr>
		</table>
		<?php
		wp_nonce_field( 'agency_pass_promote_' . $user->ID, 'agency_pass_promote_nonce' );
	}

	/**
	 * Renders the re-enroll button for a previously managed user.
	 *
	 * @param WP_User $user The user to re-enroll.
	 *
	 * @return void
	 */
	private static function render_reenroll_button( WP_User $user ): void {
		if ( ! self::current_user_is_eligible() ) {
			return;
		}

		$reenroll_url = wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'agency_pass_reenroll',
					'user_id' => $user->ID,
				],
				admin_url( 'admin-post.php' ),
			),
			'agency_pass_reenroll_' . $user->ID,
		);
		?>
		<h2><?php esc_html_e( 'Agency Pass', 'agency-pass' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Status', 'agency-pass' ); ?></th>
				<td>
					<p><?php esc_html_e( 'This user is eligible for Agency Pass management.', 'agency-pass' ); ?></p>
					<p>
						<a href="<?php echo esc_url( $reenroll_url ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Re-enroll in Agency Pass', 'agency-pass' ); ?>
						</a>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Checks whether an email is eligible for Agency Pass enrollment.
	 *
	 * @param string $email The email to check.
	 *
	 * @return bool
	 */
	private static function is_eligible_for_enrollment( string $email ): bool {
		if ( ! \defined( 'AGENCY_PASS_EMAIL_PATTERN' ) ) {
			return false;
		}

		$pattern = (string) \constant( 'AGENCY_PASS_EMAIL_PATTERN' );
		if ( ! (bool) \preg_match( $pattern, $email ) ) {
			return false;
		}

		/**
		 * Filters whether the email is allowed.
		 *
		 * @see RequestHandler::is_email_allowed()
		 *
		 * @param bool   $allowed Whether the email is allowed.
		 * @param string $email   The email address.
		 *
		 * @return bool
		 */
		return apply_filters( 'agency_pass_email_allowed', true, $email ) === true; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public API hook.
	}

	/**
	 * Checks whether the current user's email is eligible for Agency Pass.
	 *
	 * @return bool
	 */
	private static function current_user_is_eligible(): bool {
		$current = wp_get_current_user();
		if ( $current->ID === 0 ) {
			return false;
		}

		return self::is_eligible_for_enrollment( $current->user_email );
	}
}
