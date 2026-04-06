const { execSync } = require('child_process');
const { WP_CLI, wpCli: run } = require('./helpers');
const wp = WP_CLI;

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

    // Ensure admin email matches the email pattern so profile actions work.
    console.log('Checking admin email...');
    try {
        const email = run('user get 1 --field=user_email');
        console.log('Current admin email:', email);
        if (!email.endsWith('@example.tld')) {
            console.log('Updating admin email to match pattern...');
            execSync(`${wp} user update 1 --user_email=admin@example.tld`, { stdio: 'inherit' });
        }
    } catch (err) {
        console.log('Admin email update failed:', err.message);
    }
};
