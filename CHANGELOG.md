# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0]

### Added

- Self-service emergency login via magic link on the WordPress login form
- Custom `agency_pass_admin` role (administrator minus user management capabilities)
- Configurable email pattern matching via `AGENCY_PASS_EMAIL_PATTERN` constant
- Token-based authentication with configurable TTL (`AGENCY_PASS_TOKEN_TTL`)
- Temporary user creation with 8-hour expiry (`AGENCY_PASS_USER_TTL`, default 28800)
- Expired users keep their account with no role (no content orphaning)
- Auth cookie lifetime matches user TTL
- Role revocation on logout when no other sessions remain
- Mu-plugin loader for guaranteed early loading (installed on activation)
- `AuditLoggerInterface` for extensible audit trail plugin integration
- WP Activity Log (Melapress) as required audit trail dependency
- Admin notice when no audit trail plugin is active (emergency login disabled)
- Audit logging via custom actions and `error_log()` fallback
- `agency_pass_email_allowed` filter for per-site extensibility
- Hourly WP-Cron cleanup with init-based safety net
- `AGENCY_PASS_STRICT_MODE` constant to suppress email rejection feedback (prevents enumeration)
- `wp_login_failed` action on rejected emails for fail2ban / rate limiter integration
- `list_users` capability on the custom role (can view user list, cannot edit)
- `map_meta_cap` filter to block emergency users from editing their own profile
- Version-based role re-registration (capability updates without re-activation)
- Existing admin detection — sends "you have an account" email instead of magic link
- User profile UI showing Agency Pass managed status with TTL countdown
- "End Session Now" button to immediately revoke role and destroy sessions
- "Re-enroll" button for eligible users whose management was previously removed
- Automatic promotion when admin changes a managed user's role
- Admin notice on managed user profile pages warning about role change consequences
- JS confirmation dialog when changing a managed user's role with explicit promotion via hidden input
- Password reset blocked for managed users (`allow_password_reset` filter)
- Playwright E2E test coverage including full magic link flow via Mailpit

### Fixed

- Logout no longer deletes managed-user meta — users can re-request magic links after logging out
- Expired managed users are reactivated instead of failing on duplicate email creation

[Unreleased]: https://github.com/apermo/agency-pass/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/apermo/agency-pass/releases/tag/v0.1.0
