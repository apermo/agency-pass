const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

const wp = process.env.CI ? 'wp --path=/tmp/wordpress' : 'ddev wp';
const run = (cmd) => execSync(`${wp} ${cmd}`, { encoding: 'utf-8' }).trim();

test.describe('Agency Pass without audit trail plugin', () => {
    test.beforeAll(() => {
        run('plugin deactivate wp-security-audit-log');
    });

    test.afterAll(() => {
        run('plugin activate wp-security-audit-log');
    });

    test('does not show Agency Pass button on login page', async ({ browser }) => {
        const context = await browser.newContext();
        const page = await context.newPage();
        await page.goto('/wp-login.php');
        const button = page.locator('#agency-pass-toggle');
        await expect(button).not.toBeVisible();
        await context.close();
    });

    test('shows missing audit plugin warning in wp-admin', async ({ page }) => {
        await page.goto('/wp-admin/');
        const notice = page.locator('.notice-warning');
        await expect(notice).toContainText('audit trail plugin');
    });
});
