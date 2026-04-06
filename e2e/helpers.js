const { execSync } = require('child_process');

/**
 * Detect the WP-CLI command for the current environment.
 *
 * CI installs @wordpress/env globally (available as wp-env).
 * Locally, prefer ddev wp if WP_BASE_URL points to DDEV, otherwise use @wordpress/env.
 */
const WP_CLI = process.env.CI
    ? 'npx wp-env run cli wp'
    : (process.env.WP_BASE_URL || '').includes('localhost:8888')
        ? 'npx @wordpress/env run cli wp'
        : 'ddev wp';
const MAILPIT_API = process.env.MAILPIT_API_URL
    ? process.env.MAILPIT_API_URL + '/api/v1'
    : (process.env.WP_BASE_URL || 'https://agency-pass.ddev.site').replace(/:\d+$/, '') + ':8026/api/v1';

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

/**
 * Dismiss the WSAL setup wizard if it appears.
 *
 * @param {import('@playwright/test').Page} page
 */
async function dismissWsalWizard(page) {
    const modal = page.locator('.fs-modal-footer .button-deactivate, .fs-modal [data-action="skip"]');
    if (await modal.isVisible({ timeout: 2000 }).catch(() => false)) {
        await modal.click();
        await page.waitForTimeout(500);
    }
}

module.exports = { wpCli, wpCliExec, dismissWsalWizard, WP_CLI, MAILPIT_API };
