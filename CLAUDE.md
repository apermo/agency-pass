# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress must-use plugin providing self-service emergency login for agency staff on client sites. Staff with
whitelisted email addresses request a magic login link from the login screen, which creates a temporary user
with a restricted admin role.

**PHP 8.1+ minimum.** Strict types everywhere (`declare(strict_types=1)`).

## Architecture

### Plugin mode with mu-plugin loader

The plugin installs a mu-plugin loader on activation (`MuPluginInstaller`). This guarantees early loading and
prevents deactivation from the WordPress admin UI. The mu-loader requires the main `plugin.php`, which has a
double-load guard via `AGENCY_PASS_LOADED` constant.

**Entry point:** `plugin.php` (plugin header, autoloader, `Plugin::init()`)
**Source:** `src/` (PSR-4 root, namespace `Agency_Pass`)
**Mu-loader source:** `mu-loader/agency-pass-loader.php` (reference; `MuPluginInstaller` generates the actual file)

### Key conventions

- PSR-4 autoloading under `src/`
- Coding standards: `apermo/apermo-coding-standards` (PHPCS)
- Static analysis: `apermo/phpstan-wordpress-rules` + `szepeviktor/phpstan-wordpress`
- Testing: PHPUnit + Brain Monkey + Yoast PHPUnit Polyfills
- Test suites: `tests/Unit/` and `tests/Integration/`
- All classes use static methods with `[ClassName::class, 'method']` callback pattern
- Each class has a `registerHooks()` static method for wiring WordPress hooks
- Configuration via `wp-config.php` constants only (no database, no admin UI)

### Configuration constants

- `AGENCY_PASS_EMAIL_PATTERN` (required) — regex for allowed emails
- `AGENCY_PASS_TOKEN_TTL` (default: 900) — magic link validity in seconds
- `AGENCY_PASS_USER_TTL` (default: 86400) — temporary user lifetime in seconds

## Commands

```bash
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
composer test:integration # Run integration tests only
npm run test:e2e         # Run Playwright E2E tests
npm run test:e2e:ui      # Run E2E tests with UI
```

## Local Development (DDEV)

```bash
ddev start && ddev orchestrate   # Full WordPress environment
```

## Git Hooks

Pre-commit hook runs PHPCS and PHPStan on staged files. Enable with:

```bash
git config core.hooksPath .githooks
```

## CI (GitHub Actions)

- `ci.yml` — PHPCS + PHPStan + PHPUnit across PHP 8.2, 8.3, 8.4
- `integration.yml` — WP integration tests (real WP + MySQL, multisite matrix)
- `e2e.yml` — Playwright E2E tests against running WordPress
- `wp-beta.yml` — Nightly WP beta/RC compatibility check
- `release.yml` — CHANGELOG-driven releases
- `pr-validation.yml` — conventional commit and changelog checks

### Integration test environment

Integration tests run against a real WordPress instance. The bootstrap auto-detects
`vendor/wp-phpunit/wp-phpunit` when `WP_TESTS_DIR` is unset. For local development:

```bash
composer require --dev wp-phpunit/wp-phpunit
cp wp-tests-config.php.dist wp-tests-config.php  # edit DB credentials
composer test:integration
```

When neither `WP_TESTS_DIR` nor `vendor/wp-phpunit/wp-phpunit` exist, the bootstrap
skips WP loading — unit tests work unchanged.

## Template Sync

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```
