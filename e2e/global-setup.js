const { execSync } = require('child_process');

const run = (cmd) => execSync(`ddev wp ${cmd}`, { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }).trim();

/**
 * Install and activate WP Activity Log, and ensure AGENCY_PASS_EMAIL_PATTERN is set.
 */
module.exports = async function globalSetup() {
    // Install WSAL if not present.
    try {
        run('plugin is-installed wp-security-audit-log');
    } catch {
        console.log('Installing WP Activity Log...');
        execSync('ddev wp plugin install wp-security-audit-log --activate', { stdio: 'inherit' });
        return;
    }

    try {
        execSync('ddev wp plugin activate wp-security-audit-log', { stdio: 'inherit' });
    } catch {
        // Already active.
    }

    // Ensure email pattern constant is configured.
    try {
        run('config get AGENCY_PASS_EMAIL_PATTERN');
    } catch {
        console.log('Setting AGENCY_PASS_EMAIL_PATTERN...');
        execSync(
            "ddev wp config set AGENCY_PASS_EMAIL_PATTERN '/^.+@example\\.tld$/' --type=constant",
            { stdio: 'inherit' },
        );
    }
};
