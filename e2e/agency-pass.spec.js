const { test, expect } = require('@playwright/test');
const { MAILPIT_API, wpCli } = require('./helpers');

test.describe('Agency Pass login form', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('shows Agency Pass button on login page', async ({ page }) => {
        await page.goto('/wp-login.php');
        const button = page.locator('#agency-pass-toggle');
        await expect(button).toBeVisible();
        await expect(button).toHaveText('Agency Pass');
    });

    test('toggles email form on button click', async ({ page }) => {
        await page.goto('/wp-login.php');
        const form = page.locator('#agency-pass-form');
        await expect(form).toBeHidden();

        await page.locator('#agency-pass-toggle').click();
        await expect(form).toBeVisible();

        await page.locator('#agency-pass-toggle').click();
        await expect(form).toBeHidden();
    });

    test('email form contains required fields', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();

        const emailInput = page.locator('#agency-pass-email');
        await expect(emailInput).toBeVisible();
        await expect(emailInput).toHaveAttribute('type', 'email');
        await expect(emailInput).toHaveAttribute('required', '');

        const submitButton = page.locator('#agency-pass-form input[type="submit"]');
        await expect(submitButton).toBeVisible();
    });

    test('matching email shows success message', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill('test@example.tld');
        await page.locator('#agency-pass-form input[type="submit"]').click();

        await page.waitForURL(/agency_pass=sent/);
        const message = page.locator('.message');
        await expect(message).toContainText('If your email is authorized');
    });

    test('non-matching email shows rejection error', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill('nobody@invalid.tld');
        await page.locator('#agency-pass-form input[type="submit"]').click();

        await page.waitForURL(/agency_pass=rejected/);
        const error = page.locator('#login_error');
        await expect(error).toContainText('not accepted');
    });
});

test.describe('Agency Pass full login flow', () => {
    test.skip(!!process.env.CI, 'Requires Mailpit (DDEV only)');
    test.use({ storageState: { cookies: [], origins: [] } });

    const flowEmail = 'flow-test@example.tld';

    test.beforeAll(() => {
        // Remove any stale user from previous runs.
        try {
            const userId = wpCli(`user get ${flowEmail} --field=ID`);
            wpCli(`user delete ${userId} --yes`);
        } catch {
            // No stale user.
        }
    });

    test('magic link creates emergency user and logs in', async ({ page, request }) => {
        // Clear Mailpit inbox.
        await request.delete(MAILPIT_API + '/messages', { ignoreHTTPSErrors: true });

        // Request a magic link.
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill(flowEmail);
        await page.locator('#agency-pass-form input[type="submit"]').click();
        await page.waitForURL(/agency_pass=sent/);

        // Fetch the email from Mailpit.
        const messages = await request.get(MAILPIT_API + '/messages', { ignoreHTTPSErrors: true });
        const body = await messages.json();
        expect(body.messages.length).toBeGreaterThan(0);

        const mail = body.messages.find(m => m.To[0].Address === flowEmail);
        expect(mail).toBeDefined();
        expect(mail.Subject).toContain('emergency login link');

        // Fetch the full message body to get the complete token.
        const fullMsg = await request.get(MAILPIT_API + '/message/' + mail.ID, { ignoreHTTPSErrors: true });
        const fullBody = await fullMsg.json();
        const tokenMatch = fullBody.Text.match(/token=([a-f0-9]{64})/);
        expect(tokenMatch).not.toBeNull();
        const token = tokenMatch[1];

        // Click the magic link.
        await page.goto('/wp-admin/admin-post.php?action=agency_pass_login&token=' + token);

        // Verify we landed in wp-admin as the emergency user.
        await expect(page).toHaveURL(/wp-admin/);
        await expect(page.locator('#wpadminbar')).toBeVisible();

        // Verify the username starts with agencypass-.
        const userDisplay = page.locator('#wp-admin-bar-my-account .display-name').first();
        await expect(userDisplay).toContainText('agencypass-');
    });
});
