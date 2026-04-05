const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

const MAILPIT_API = (process.env.WP_BASE_URL || 'https://agency-pass.ddev.site').replace(/:\d+$/, '') + ':8026/api/v1';
const wp = process.env.CI ? 'wp --path=/tmp/wordpress' : 'ddev wp';
const run = (cmd) => execSync(`${wp} ${cmd}`, { encoding: 'utf-8' }).trim();

test.describe('Agency Pass with existing admin account', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test.beforeAll(() => {
        // Create a real admin user with an email matching the Agency Pass pattern.
        try {
            run('user create realadmin realadmin@example.tld --role=administrator --user_pass=testpass123');
        } catch {
            // User may already exist from a previous run.
        }
    });

    test.afterAll(() => {
        try {
            run('user delete realadmin --yes');
        } catch {
            // Already cleaned up.
        }
    });

    test('existing admin gets "you have an account" email instead of magic link', async ({ page, request }) => {
        // Clear Mailpit inbox.
        await request.delete(MAILPIT_API + '/messages', { ignoreHTTPSErrors: true });

        // Submit the Agency Pass form with the existing admin's email.
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill('realadmin@example.tld');
        await page.locator('#agency-pass-form input[type="submit"]').click();

        // Should still show the generic success message (no enumeration).
        await page.waitForURL(/agency_pass=sent/);
        const message = page.locator('.message');
        await expect(message).toContainText('If your email is authorized');

        // Check Mailpit — should have "You already have an account", not a magic link.
        const messages = await request.get(MAILPIT_API + '/messages', { ignoreHTTPSErrors: true });
        const body = await messages.json();
        expect(body.messages.length).toBeGreaterThan(0);

        const mail = body.messages.find(m => m.To[0].Address === 'realadmin@example.tld');
        expect(mail).toBeDefined();
        expect(mail.Subject).toContain('You already have an account');
        expect(mail.Subject).not.toContain('emergency login link');

        // Verify the email body contains login and password reset links.
        const fullMsg = await request.get(MAILPIT_API + '/message/' + mail.ID, { ignoreHTTPSErrors: true });
        const fullBody = await fullMsg.json();
        expect(fullBody.Text).toContain('wp-login.php');
        expect(fullBody.Text).toContain('lostpassword');
    });
});
