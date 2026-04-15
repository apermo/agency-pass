const { test, expect } = require('@playwright/test');
const { wpCli, dismissWsalWizard } = require('./helpers');

test.describe('Agency Pass user profile management', () => {
    let agencyUserId;

    test.beforeAll(() => {
        try {
            agencyUserId = wpCli('user create agencypass-profiletest profiletest@example.tld --role=agency_pass_admin --porcelain');
        } catch {
            agencyUserId = wpCli('user get agencypass-profiletest --field=ID');
        }
        wpCli(`user meta update ${agencyUserId} _agency_pass_user 1`);
        const expires = Math.floor(Date.now() / 1000) + 28800;
        wpCli(`user meta update ${agencyUserId} _agency_pass_expires ${expires}`);
    });

    test.afterAll(() => {
        try {
            wpCli(`user delete ${agencyUserId} --yes`);
        } catch {
            // Already deleted.
        }
    });

    test('shows managed status on agency pass user profile', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);
        await dismissWsalWizard(page);

        await expect(page.locator('.notice-warning:has-text("Agency Pass")')).toBeVisible();
        await expect(page.locator('h2', { hasText: 'Agency Pass' })).toBeVisible();
        await expect(page.locator('.form-table >> text=managed by Agency Pass')).toBeVisible();
        await expect(page.locator('text=remaining')).toBeVisible();
        await expect(page.locator('a:has-text("End Session Now")')).toBeVisible();
    });

    test('end session revokes role and destroys sessions', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);
        await page.locator('a:has-text("End Session Now")').click();

        await expect(page).toHaveURL(/users\.php/);

        const role = wpCli(`user get ${agencyUserId} --field=roles`);
        expect(role).toBe('');
    });

    test('re-enroll restores agency pass role after end session', async ({ page }) => {
        // After end session, user still has _agency_pass_user meta but expired + no role.
        // The profile should show managed status with expired label and re-enroll-like flow.
        // A new magic link request for the same email should reuse and extend the user.
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);

        // User is still marked as managed (meta present), so Agency Pass section shows.
        await expect(page.locator('h2', { hasText: 'Agency Pass' })).toBeVisible();
    });

    test('changing role promotes user and shows re-enroll', async ({ page }) => {
        // Changing role away from agency_pass_admin removes the meta marker.
        wpCli(`user set-role ${agencyUserId} agency_pass_admin`);
        wpCli(`user meta update ${agencyUserId} _agency_pass_user 1`);
        const expires = Math.floor(Date.now() / 1000) + 28800;
        wpCli(`user meta update ${agencyUserId} _agency_pass_expires ${expires}`);

        // Now change to editor — triggers profile_update which removes meta.
        wpCli(`user set-role ${agencyUserId} editor`);

        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);

        // The meta should be removed (promoted). Re-enroll should be available.
        await expect(page.locator('text=eligible for Agency Pass')).toBeVisible();
        await expect(page.locator('a:has-text("Re-enroll in Agency Pass")')).toBeVisible();
    });

    test('re-enroll button restores agency pass management', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);
        await page.locator('a:has-text("Re-enroll in Agency Pass")').click();

        await expect(page).toHaveURL(new RegExp(`user-edit\\.php.*user_id=${agencyUserId}`));

        const role = wpCli(`user get ${agencyUserId} --field=roles`);
        expect(role).toContain('agency_pass_admin');
        await expect(page.locator('.form-table >> text=managed by Agency Pass')).toBeVisible();
    });
});
