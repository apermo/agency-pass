<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Integrates the Agency Pass UI into the WordPress login form.
 */
class LoginForm {

	/**
	 * Registers hooks for the login form.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'login_footer', [ self::class, 'render' ] );
		add_action( 'login_enqueue_scripts', [ self::class, 'enqueue_styles' ] );
		add_filter( 'login_message', [ self::class, 'confirmation_message' ] );
		add_action( 'login_footer', [ self::class, 'maybe_shake' ], 12 );
	}

	/**
	 * Renders the Agency Pass UI below the standard login fields.
	 *
	 * @return void
	 */
	public static function render(): void {
		$action_url = admin_url( 'admin-post.php' );
		$nonce      = wp_create_nonce( 'agency_pass_request' );
		?>
		<div id="agency-pass-wrapper">
			<button type="button" id="agency-pass-toggle" class="button button-large">
				<?php esc_html_e( 'Agency Pass', 'agency-pass' ); ?>
			</button>
			<div id="agency-pass-form" style="display:none;">
				<form method="post" action="<?php echo esc_url( $action_url ); ?>">
					<input type="hidden" name="action" value="agency_pass_request" />
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
					<p>
						<label for="agency-pass-email"><?php esc_html_e( 'Email Address', 'agency-pass' ); ?></label>
						<input type="email" name="email" id="agency-pass-email" class="input" required />
					</p>
					<p class="submit">
						<input type="submit" class="button button-primary button-large"
							value="<?php esc_attr_e( 'Send Login Link', 'agency-pass' ); ?>" />
					</p>
				</form>
			</div>
		</div>
		<script>
			(function() {
				var wrapper = document.getElementById('agency-pass-wrapper');
				var loginForm = document.getElementById('loginform');
				if (loginForm && wrapper) {
					loginForm.parentNode.insertBefore(wrapper, loginForm.nextSibling);
				}
				document.getElementById('agency-pass-toggle').addEventListener('click', function() {
					var form = document.getElementById('agency-pass-form');
					form.style.display = form.style.display === 'none' ? 'block' : 'none';
				});
			})();
		</script>
		<?php
	}

	/**
	 * Shows a result message after a magic link request.
	 *
	 * @param string $message The existing login message.
	 *
	 * @return string
	 */
	public static function confirmation_message( string $message ): string {
		if ( ! isset( $_GET['agency_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.
			return $message;
		}

		$result = sanitize_text_field( wp_unslash( $_GET['agency_pass'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.

		if ( $result === 'rejected' ) {
			return '<div id="login_error"><strong>'
				. esc_html__( 'Error:', 'agency-pass' )
				. '</strong> '
				. esc_html__( 'Your email address is not accepted.', 'agency-pass' )
				. '</div>';
		}

		return '<p class="message">'
			. esc_html__( 'If your email is authorized, you will receive a login link shortly.', 'agency-pass' )
			. '</p>';
	}

	/**
	 * Triggers the login form shake animation on rejection.
	 *
	 * Mirrors the wp_shake_js() behavior from WordPress core.
	 *
	 * @return void
	 */
	public static function maybe_shake(): void {
		if ( ! isset( $_GET['agency_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.
			return;
		}

		$result = sanitize_text_field( wp_unslash( $_GET['agency_pass'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only, no state change.
		if ( $result !== 'rejected' ) {
			return;
		}

		wp_print_inline_script_tag( "document.querySelector('form').classList.add('shake');" );
	}

	/**
	 * Enqueues minimal styles for the Agency Pass login UI.
	 *
	 * @return void
	 */
	public static function enqueue_styles(): void {
		?>
		<style>
			#agency-pass-wrapper {
				margin-top: 20px;
				padding: 26px 24px;
				background: #fff;
				border: 1px solid #c3c4c7;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
				text-align: center;
			}
			#agency-pass-toggle {
				width: 100%;
			}
			#agency-pass-form {
				margin-top: 12px;
				text-align: left;
			}
			#agency-pass-form form {
				margin: 0;
				padding: 0;
				border: none;
				box-shadow: none;
				background: none;
			}
			#agency-pass-form .input {
				width: 100%;
			}
			#agency-pass-form .submit {
				text-align: center;
			}
			#agency-pass-form .submit .button {
				width: 100%;
			}
		</style>
		<?php
	}
}
