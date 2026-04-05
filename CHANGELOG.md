# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Self-service emergency login via magic link on the WordPress login form
- Custom `agency_pass_admin` role (administrator minus user management capabilities)
- Configurable email pattern matching via `AGENCY_PASS_EMAIL_PATTERN` constant
- Token-based authentication with configurable TTL (`AGENCY_PASS_TOKEN_TTL`)
- Temporary user creation with automatic expiry and cleanup (`AGENCY_PASS_USER_TTL`)
- Mu-plugin loader for guaranteed early loading (installed on activation)
- Audit logging via custom actions and `error_log()` fallback
- `agency_pass_email_allowed` filter for per-site extensibility
- Hourly WP-Cron cleanup with init-based safety net
- `AGENCY_PASS_STRICT_MODE` constant to suppress email rejection feedback (prevents enumeration)
- `wp_login_failed` action on rejected emails for fail2ban / rate limiter integration
- `list_users` capability on the custom role (can view user list, cannot edit)
- `map_meta_cap` filter to block emergency users from editing their own profile
- Version-based role re-registration (capability updates without re-activation)
- Full E2E test coverage via Playwright + Mailpit (magic link flow)

### Fixed

- Login form rendered outside WordPress `<form>` to avoid nested form issues
- User lookup by email uses `search` + `search_columns` instead of non-existent `email` parameter

### Changed

- DDEV docroot moved to `.ddev/wordpress/` to keep project root clean
