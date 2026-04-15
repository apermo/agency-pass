const { test } = require('@playwright/test');

const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const WP_ADMIN_PASSWORD = process.env.WP_ADMIN_PASSWORD || (process.env.CI ? 'password' : 'admin');

test('authenticate as admin', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill(WP_ADMIN_USER);
    await page.locator('#user_pass').fill(WP_ADMIN_PASSWORD);
    await page.locator('#wp-submit').click();
    await page.waitForURL(/wp-admin/);

    // Dismiss WSAL setup wizard if it appears.
    const skipButton = page.locator('.fs-modal-footer .button-deactivate, [data-action="skip"]');
    if (await skipButton.isVisible({ timeout: 2000 }).catch(() => false)) {
        await skipButton.click();
        await page.waitForTimeout(1000);
    }

    await page.context().storageState({ path: '.auth/admin.json' });
});
