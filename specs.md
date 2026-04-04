# Agency Pass — Plugin Specification

## Overview

**Agency Pass** is a WordPress must-use plugin that provides self-service emergency login for agency staff on client sites. Staff with a whitelisted email address can request a magic login link from the WordPress login screen, which creates a temporary user with a restricted admin role.

No external infrastructure required. No OAuth, no broker, no signing keys. The entire solution is self-contained in a single plugin with a mu-plugin loader for guaranteed early loading.

## Core Flow

1. A button labeled "Agency Pass" is added to the WordPress login form.
2. Clicking it reveals an email input field.
3. On submit, the email is validated against a configurable regex pattern.
4. If the email matches, a single-use magic link with a short-lived token is sent via `wp_mail()`.
5. On clicking the magic link, the token is validated.
6. A temporary user (e.g., `emergency-christoph`) is created with a custom restricted role.
7. The user is logged in via `wp_set_auth_cookie()`.
8. A WP-Cron job cleans up expired emergency users after a configurable TTL.

## Configuration

Configuration is done via constants defined in `wp-config.php`. No admin UI, no database config, no options table.

### Required

```php
define( 'AGENCY_PASS_EMAIL_PATTERN', '/^.+@youragency\.de$/' );
```

### Optional (with defaults)

```php
define( 'AGENCY_PASS_TOKEN_TTL', 900 );    // Magic link validity in seconds, default 15 minutes
define( 'AGENCY_PASS_USER_TTL', 86400 );    // Temporary user lifetime in seconds, default 24 hours
```

## Custom Role: `agency_pass_admin`

Cloned from `administrator` with the following capabilities removed:

- `edit_users`
- `delete_users`
- `create_users`
- `list_users`
- `promote_users`
- `remove_users`

The emergency user can do everything needed for debugging (activate/deactivate plugins, edit options, view posts, etc.) but cannot touch user accounts or elevate privileges.

The role is registered on plugin activation and removed on deactivation/uninstall. The mu-loader removal and role cleanup both happen during deactivation.

## Token Handling

- Tokens are stored as WordPress transients.
- Transient key: `agency_pass_token_{$token}`.
- Transient value: array containing `email`, `created_at`, and optionally `ip`.
- TTL: Controlled by `AGENCY_PASS_TOKEN_TTL`.
- Tokens are single-use — deleted immediately after successful validation.
- Token should be a cryptographically secure random string (`wp_generate_password(64, false)` or `bin2hex(random_bytes(32))`).

## Temporary User Lifecycle

### Creation

- Username: `agencypass-{localpart}` derived from the email (e.g., `christoph@agency.de` → `agencypass-christoph`). Handle collisions by appending a short random suffix.
- Email: The requesting user's email address.
- Password: Random 64-character string, never exposed.
- Role: `agency_pass_admin`.
- User meta `_agency_pass_expires`: Unix timestamp of expiry (`time() + AGENCY_PASS_USER_TTL`).
- User meta `_agency_pass_user`: `true` (marker for identification and cleanup).

### Cleanup

- A WP-Cron event (`agency_pass_cleanup`) runs hourly.
- It queries users with `_agency_pass_user` meta, checks `_agency_pass_expires`, and deletes expired users via `wp_delete_user()` (without reassigning content).
- Additionally, consider cleanup on `init` as a safety net if cron is unreliable.

### Reuse

If an active (non-expired) emergency user already exists for the same email, reuse it and extend the TTL instead of creating a new one.

## Login Form Integration

- Hook into `login_form` to add the Agency Pass UI below the standard login fields.
- The UI should be minimal: a button that toggles an email input field and a submit button.
- The emergency login form submits to a custom handler (e.g., `admin_post_nopriv_agency_pass_request`).
- After successful submission, show a confirmation message: "If your email is authorized, you will receive a login link shortly." (Do not reveal whether the email matched — prevents enumeration.)
- The magic link URL should point to a custom handler (e.g., `admin_post_nopriv_agency_pass_login`) with the token as a query parameter.

## Audit Logging

### WP Activity Log Integration (Optional Dependency)

If WP Activity Log (Melapress) is active, fire custom events into their sensor system for:

- Magic link requested (email, IP, timestamp, success/failure)
- Emergency login performed (email, username created, IP, timestamp)
- Emergency user expired and cleaned up

Do not hard-depend on the plugin. Check for availability at runtime.

### Fallback

- Fire a custom action for all auditable events:
  - `do_action( 'agency_pass_link_requested', $email, $ip, $matched )`
  - `do_action( 'agency_pass_login', $email, $username, $ip )`
  - `do_action( 'agency_pass_user_cleanup', $username )`
- As a last resort, log to `error_log()`.

## Filter for Extensibility

```php
apply_filters( 'agency_pass_email_allowed', bool $allowed, string $email ): bool
```

Called after the regex check passes. Allows per-site overrides, explicit blocklists, or additional validation logic. The regex is the fast first gate, this filter is the escape hatch.

## Security Considerations

- Magic link tokens are single-use and short-lived.
- The confirmation message on the login form must not reveal whether the email matched (prevent email enumeration).
- The emergency user gets a random impossible password.
- The custom role explicitly cannot manage other users.
- If `AGENCY_PASS_EMAIL_PATTERN` is not defined, the plugin should do nothing (fail closed).
- Nonce protection on the email submission form.
- Rate limiting on magic link requests is a nice-to-have (e.g., max 3 requests per email per hour via transients).

## Plugin Architecture

Use [apermo/template-wordpress](https://github.com/apermo/template-wordpress) as the project template. Standard WordPress plugin that installs a mu-plugin loader on activation, following the pattern from [apermo/site-bookkeeper-reporter](https://github.com/apermo/site-bookkeeper-reporter).

### Why

A regular plugin can be deactivated from the admin — which defeats the purpose of emergency access. The mu-plugin loader guarantees the plugin loads early and cannot be disabled through the WordPress UI. At the same time, keeping it as a regular plugin allows normal distribution, updates, and a proper activation/deactivation lifecycle.

### File Structure

```
agency-pass/
  agency-pass.php              # Main plugin file (plugin header, bootstrap)
  src/                         # Plugin logic (classes, hooks, handlers)
  mu-loader/
    agency-pass-loader.php     # Mu-plugin loader, copied to mu-plugins/ on activation
```

### Mu-Loader Behavior

- On plugin activation: copy `mu-loader/agency-pass-loader.php` to `wp-content/mu-plugins/agency-pass-loader.php`.
- The loader checks if the main plugin file exists and requires it. If the plugin has been deleted without proper deactivation, the loader does nothing (no fatal errors).
- On plugin deactivation: remove the mu-plugin loader from `wp-content/mu-plugins/`.
- The loader file should contain a reference comment pointing back to the parent plugin for traceability.

## Coding Standards

Inherited from `apermo/template-wordpress` (camelCase, no Yoda conditions, short array syntax, type hints, Composer autoloading).

## Out of Scope (Future Versions)

- Admin UI for configuration
- Configurable role capabilities via constants or filters
- Slack/email notifications on emergency login
- Dashboard widget showing active emergency users
- `install_plugins` / `install_themes` capability toggle
- IP-binding for tokens
