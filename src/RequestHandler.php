<?php

declare(strict_types=1);

namespace Agency_Pass;

/**
 * Handles magic link request and login actions.
 */
class RequestHandler {

	/**
	 * Register hooks for request handling.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'admin_post_nopriv_agency_pass_request', [ self::class, 'handle_request' ] );
		add_action( 'admin_post_nopriv_agency_pass_login', [ self::class, 'handle_login' ] );
	}

	/**
	 * Handle the magic link request form submission.
	 *
	 * @return void
	 */
	public static function handle_request(): void {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'agency_pass_request' ) ) {
			wp_die(
				esc_html__( 'Invalid request.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$matched = self::is_email_allowed( $email );

		/**
		 * Fires when a magic link is requested.
		 *
		 * @param string $email   The requesting email address.
		 * @param string $ip    The requesting IP address.
		 * @param bool   $matched Whether the email matched the allowed pattern.
		 */
		do_action( 'agency_pass_link_requested', $email, $ip, $matched ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( $matched ) {
			$token = Token::generate( $email, $ip );
			self::send_magic_link( $email, $token );
		}

		// Generic message to prevent email enumeration.
		self::redirect_with_message();
	}

	/**
	 * Handle the magic link login.
	 *
	 * @return void
	 */
	public static function handle_login(): void {
		$token_string = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $token_string === '' ) {
			wp_die(
				esc_html__( 'Invalid or expired login link.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		$token_data = Token::validate( $token_string );

		if ( $token_data === null ) {
			wp_die(
				esc_html__( 'Invalid or expired login link.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 403 ],
			);
		}

		$email   = $token_data['email'];
		$ip      = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$user_id = UserManager::create_or_reuse( $email );
		$user    = get_userdata( $user_id );

		if ( $user === false ) {
			wp_die(
				esc_html__( 'Could not create emergency user.', 'agency-pass' ),
				esc_html__( 'Error', 'agency-pass' ),
				[ 'response' => 500 ],
			);
		}

		wp_set_auth_cookie( $user_id, false );
		wp_set_current_user( $user_id );

		/**
		 * Fires when an emergency login is performed.
		 *
		 * @param string $email    The email address used.
		 * @param string $username The username created or reused.
		 * @param string $ip     The requesting IP address.
		 */
		do_action( 'agency_pass_login', $email, $user->user_login, $ip ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		wp_safe_redirect( admin_url() );
		exit();
	}

	/**
	 * Check whether the given email is allowed.
	 *
	 * @param string $email The email to check.
	 *
	 * @return bool
	 */
	private static function is_email_allowed( string $email ): bool {
		if ( $email === '' ) {
			return false;
		}

		$pattern = (string) \constant( 'AGENCY_PASS_EMAIL_PATTERN' );
		$matched = (bool) \preg_match( $pattern, $email );

		if ( $matched ) {
			/**
			 * Filters whether the email is allowed after the regex check passes.
			 *
			 * Allows per-site overrides, explicit blocklists, or additional
			 * validation logic.
			 *
			 * @param bool   $allowed Whether the email is allowed.
			 * @param string $email   The email address.
			 *
			 * @return bool
			 */
			$matched = apply_filters( 'agency_pass_email_allowed', true, $email ) === true; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		return $matched;
	}

	/**
	 * Send the magic login link via wp_mail().
	 *
	 * @param string $email The recipient email.
	 * @param string $token The magic link token.
	 *
	 * @return void
	 */
	private static function send_magic_link( string $email, string $token ): void {
		$login_url = add_query_arg(
			[
				'action' => 'agency_pass_login',
				'token'  => $token,
			],
			admin_url( 'admin-post.php' ),
		);

		$site_name = get_bloginfo( 'name' );
		$subject   = \sprintf(
			/* translators: %s: site name */
			__( '[%s] Your emergency login link', 'agency-pass' ),
			$site_name,
		);

		$message = \sprintf(
			/* translators: 1: site name, 2: login URL, 3: TTL in minutes */
			__(
				"You requested emergency access to %1\$s.\n\nClick the link below to log in:\n%2\$s\n\nThis link is valid for %3\$d minutes and can only be used once.",
				'agency-pass',
			),
			$site_name,
			$login_url,
			(int) \ceil( Token::ttl() / 60 ),
		);

		wp_mail( $email, $subject, $message );
	}

	/**
	 * Redirect back to the login page with a generic confirmation message.
	 *
	 * @return void
	 */
	private static function redirect_with_message(): void {
		$redirect_url = add_query_arg(
			'agency_pass_sent',
			'1',
			wp_login_url(),
		);

		wp_safe_redirect( $redirect_url );
		exit();
	}
}
