const { test, expect } = require('@playwright/test');

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

    test('submitting email shows generic confirmation', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill('test@example.tld');
        await page.locator('#agency-pass-form input[type="submit"]').click();

        await page.waitForURL(/agency_pass_sent=1/);
        const message = page.locator('.message');
        await expect(message).toContainText('If your email is authorized');
    });

    test('confirmation message does not reveal email match status', async ({ page }) => {
        // Submit with a non-matching email — same generic message should appear.
        await page.goto('/wp-login.php');
        await page.locator('#agency-pass-toggle').click();
        await page.locator('#agency-pass-email').fill('nobody@invalid.tld');
        await page.locator('#agency-pass-form input[type="submit"]').click();

        await page.waitForURL(/agency_pass_sent=1/);
        const message = page.locator('.message');
        await expect(message).toContainText('If your email is authorized');
    });
});
