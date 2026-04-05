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

== Frequently Asked Questions ==

= Does Agency Pass work with NinjaFirewall? =

NinjaFirewall blocks on-the-fly creation of users with elevated roles, which prevents Agency Pass from
creating emergency users. The relevant hooks are `nfw_account_creation`, `nfwhook_update_user_meta`, and
`nfwhook_add_user_meta`. A bypass is possible by removing these hooks before user creation, but this is
not shipped with the plugin.

== Changelog ==

= 0.1.0 =
* Initial release
