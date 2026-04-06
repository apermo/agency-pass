# Agency Pass

[![PHP CI](https://github.com/apermo/agency-pass/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/agency-pass/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/apermo/agency-pass/graph/badge.svg)](https://codecov.io/gh/apermo/agency-pass)
[![Packagist Version](https://img.shields.io/packagist/v/apermo/agency-pass)](https://packagist.org/packages/apermo/agency-pass)
[![PHP Version](https://img.shields.io/packagist/dependency-v/apermo/agency-pass/php)](composer.json)
[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-21759b)](https://wordpress.org/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)
[![Donate](https://img.shields.io/badge/Donate-PayPal-009cde)](https://paypal.me/apermo)

Self-service emergency login for agency staff on client WordPress sites.

## Description

Agency Pass adds a magic-link login flow to the WordPress login screen. Staff with whitelisted email addresses
can request a temporary login link that creates a time-limited user with a restricted admin role. No external
infrastructure required — the entire solution is self-contained.

## Requirements

- PHP 8.1+
- WordPress 6.2+

## Installation

1. Upload the `agency-pass` directory to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" screen in WordPress
3. On activation, a mu-plugin loader is installed to guarantee early loading

## Configuration

Add constants to `wp-config.php`:

### Required

```php
define( 'AGENCY_PASS_EMAIL_PATTERN', '/^.+@youragency\.de$/' );
```

### Optional

```php
define( 'AGENCY_PASS_TOKEN_TTL', 900 );    // Magic link validity in seconds (default: 15 min)
define( 'AGENCY_PASS_USER_TTL', 28800 );   // Temporary user lifetime in seconds (default: 8 h)
```

## How it works

1. A "Agency Pass" button appears on the WordPress login form
2. Clicking it reveals an email input field
3. If the email matches the configured pattern, a single-use magic link is sent via `wp_mail()`
4. Clicking the link creates a temporary user with the `agency_pass_admin` role and logs them in
5. Expired users are cleaned up automatically via WP-Cron

## Custom role

The `agency_pass_admin` role has all administrator capabilities except:

- `edit_users`, `delete_users`, `create_users`
- `promote_users`, `remove_users`

The role retains `list_users` (can view the user list). Emergency users are blocked from editing their own
profile via `map_meta_cap`.

## Extensibility

```php
add_filter( 'agency_pass_email_allowed', function ( bool $allowed, string $email ): bool {
    // Custom validation logic here
    return $allowed;
}, 10, 2 );
```

## Audit hooks

- `agency_pass_link_requested` — fired when a magic link is requested
- `agency_pass_login` — fired on successful emergency login
- `agency_pass_user_cleanup` — fired when an expired user is removed

## Development

```bash
composer install
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
```

### Local WordPress Environment

```bash
ddev start && ddev orchestrate
```

### Git Hooks

```bash
git config core.hooksPath .githooks
```

## Known incompatibilities

### NinjaFirewall

NinjaFirewall blocks on-the-fly creation of users with elevated roles. Since Agency Pass creates temporary
admin-level users via `wp_insert_user()`, NinjaFirewall will silently prevent emergency logins from working.

The relevant NinjaFirewall hooks are `nfw_account_creation` (action on `pre_user_login`),
`nfwhook_update_user_meta` and `nfwhook_add_user_meta` (filters on user meta operations). A bypass is
possible by removing these hooks before user creation, but this is not recommended and not shipped with the
plugin.

## Template Sync

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```

## License

[GPL-2.0-or-later](LICENSE)
