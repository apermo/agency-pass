=== Agency Pass ===
Contributors: flavor
Tags: login, emergency, agency, magic-link, security
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-service emergency login for agency staff on client WordPress sites.

== Description ==

Agency Pass adds a magic-link login flow to the WordPress login screen. Staff with whitelisted email addresses
can request a temporary login link that creates a time-limited user with a restricted admin role.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/agency-pass/`
2. Activate the plugin through the "Plugins" screen in WordPress
3. Add `AGENCY_PASS_EMAIL_PATTERN` to your `wp-config.php`

== Changelog ==

= 0.1.0 =
* Initial release
