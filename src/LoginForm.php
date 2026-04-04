<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Integrates the Agency Pass UI into the WordPress login form.
 */
class LoginForm {

	/**
	 * Register hooks for the login form.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'login_form', [ self::class, 'render' ] );
		add_action( 'login_enqueue_scripts', [ self::class, 'enqueue_styles' ] );
		add_filter( 'login_message', [ self::class, 'confirmation_message' ] );
	}

	/**
	 * Render the Agency Pass UI below the standard login fields.
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
			document.getElementById('agency-pass-toggle').addEventListener('click', function() {
				var form = document.getElementById('agency-pass-form');
				form.style.display = form.style.display === 'none' ? 'block' : 'none';
			});
		</script>
		<?php
	}

	/**
	 * Show a confirmation message after a magic link request.
	 *
	 * @param string $message The existing login message.
	 *
	 * @return string
	 */
	public static function confirmation_message( string $message ): string {
		if ( ! isset( $_GET['agency_pass_sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $message;
		}

		return '<p class="message">'
			. esc_html__( 'If your email is authorized, you will receive a login link shortly.', 'agency-pass' )
			. '</p>';
	}

	/**
	 * Enqueue minimal styles for the Agency Pass login UI.
	 *
	 * @return void
	 */
	public static function enqueue_styles(): void {
		?>
		<style>
			#agency-pass-wrapper {
				margin-top: 16px;
				padding-top: 16px;
				border-top: 1px solid #dcdcde;
				text-align: center;
			}
			#agency-pass-toggle {
				width: 100%;
			}
			#agency-pass-form {
				margin-top: 12px;
				text-align: left;
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
