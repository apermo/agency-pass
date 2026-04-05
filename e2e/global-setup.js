const { execSync } = require('child_process');

/**
 * Detect the WP-CLI command for the current environment.
 */
function wpCli() {
    if (process.env.CI) {
        return 'wp --path=/tmp/wordpress';
    }
    return 'ddev wp';
}

/**
 * Install and activate WP Activity Log, and ensure AGENCY_PASS_EMAIL_PATTERN is set.
 */
module.exports = async function globalSetup() {
    const wp = wpCli();

    // Install WSAL if not present.
    try {
        execSync(`${wp} plugin is-installed wp-security-audit-log 2>&1`, { encoding: 'utf-8' });
    } catch {
        console.log('Installing WP Activity Log...');
        execSync(`${wp} plugin install wp-security-audit-log`, { stdio: 'inherit' });
    }

    try {
        execSync(`${wp} plugin activate wp-security-audit-log`, { stdio: 'inherit' });
    } catch {
        // Already active.
    }

    // Ensure email pattern constant is configured.
    try {
        execSync(`${wp} config get AGENCY_PASS_EMAIL_PATTERN 2>&1`, { encoding: 'utf-8' });
    } catch {
        console.log('Setting AGENCY_PASS_EMAIL_PATTERN...');
        execSync(
            `${wp} config set AGENCY_PASS_EMAIL_PATTERN '/^.+@example\\.tld$/' --type=constant`,
            { stdio: 'inherit' },
        );
    }
};
