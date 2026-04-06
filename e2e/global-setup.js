const { execSync } = require('child_process');

/**
 * Detect the WP-CLI command prefix for the current environment.
 *
 * CI uses wp-env, local dev uses DDEV.
 */
function wpCli() {
    if (process.env.CI) {
        return 'npx wp-env run cli wp';
    }
    return 'ddev wp';
}

const wp = wpCli();
const run = (cmd) => execSync(`${wp} ${cmd}`, { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }).trim();

/**
 * Install and activate WP Activity Log, and ensure AGENCY_PASS_EMAIL_PATTERN is set.
 */
module.exports = async function globalSetup() {
    // Install WSAL if not present.
    try {
        run('plugin is-installed wp-security-audit-log');
    } catch {
        console.log('Installing WP Activity Log...');
        execSync(`${wp} plugin install wp-security-audit-log --activate`, { stdio: 'inherit' });
        return;
    }

    try {
        execSync(`${wp} plugin activate wp-security-audit-log`, { stdio: 'inherit' });
    } catch {
        // Already active.
    }

    // In CI, AGENCY_PASS_EMAIL_PATTERN is set via .wp-env.json config.
    // For local DDEV, ensure it's in wp-config.php.
    if (!process.env.CI) {
        try {
            run('config get AGENCY_PASS_EMAIL_PATTERN');
        } catch {
            console.log('Setting AGENCY_PASS_EMAIL_PATTERN...');
            execSync(
                `${wp} config set AGENCY_PASS_EMAIL_PATTERN '/^.+@example\\.tld$/' --type=constant`,
                { stdio: 'inherit' },
            );
        }
    }
};
