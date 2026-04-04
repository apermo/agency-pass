<?php
/**
 * Plugin Name: Agency Pass
 * Description: Self-service emergency login for agency staff on client sites.
 * Version:     0.1.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: agency-pass
 * Requires at least: 6.2
 * Requires PHP: 8.1
 */

declare(strict_types=1);

namespace Agency_Pass;

// Prevent double-loading when active as both regular plugin and mu-plugin.
if ( \defined( 'AGENCY_PASS_LOADED' ) ) {
	return;
}
\define( 'AGENCY_PASS_LOADED', true );

\defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init( __FILE__ );
