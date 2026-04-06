const { execSync } = require('child_process');

const WP_CLI = process.env.CI ? 'npx wp-env run cli wp' : 'ddev wp';
const MAILPIT_API = (process.env.WP_BASE_URL || 'https://agency-pass.ddev.site').replace(/:\d+$/, '') + ':8026/api/v1';

/**
 * Run a WP-CLI command and return the trimmed output.
 *
 * @param {string} cmd The WP-CLI subcommand (e.g. 'plugin list').
 * @returns {string}
 */
function wpCli(cmd) {
    return execSync(`${WP_CLI} ${cmd}`, { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }).trim();
}

/**
 * Run a WP-CLI command with inherited stdio (visible output).
 *
 * @param {string} cmd The WP-CLI subcommand.
 */
function wpCliExec(cmd) {
    execSync(`${WP_CLI} ${cmd}`, { stdio: 'inherit' });
}

module.exports = { wpCli, wpCliExec, WP_CLI, MAILPIT_API };
