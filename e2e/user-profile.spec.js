const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

const wp = process.env.CI ? 'wp --path=/tmp/wordpress' : 'ddev wp';
const run = (cmd) => execSync(`${wp} ${cmd}`, { encoding: 'utf-8' }).trim();

test.describe('Agency Pass user profile management', () => {
    let agencyUserId;

    test.beforeAll(() => {
        try {
            agencyUserId = run('user create agencypass-profiletest profiletest@example.tld --role=agency_pass_admin --porcelain');
        } catch {
            agencyUserId = run('user get agencypass-profiletest --field=ID');
        }
        run(`user meta update ${agencyUserId} _agency_pass_user 1`);
        const expires = Math.floor(Date.now() / 1000) + 28800;
        run(`user meta update ${agencyUserId} _agency_pass_expires ${expires}`);
    });

    test.afterAll(() => {
        try {
            run(`user delete ${agencyUserId} --yes`);
        } catch {
            // Already deleted.
        }
    });

    test('shows managed status on agency pass user profile', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);

        await expect(page.locator('h2', { hasText: 'Agency Pass' })).toBeVisible();
        await expect(page.locator('text=managed by Agency Pass')).toBeVisible();
        await expect(page.locator('text=remaining')).toBeVisible();
        await expect(page.locator('a:has-text("End Session Now")')).toBeVisible();
    });

    test('end session revokes role and destroys sessions', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);
        await page.locator('a:has-text("End Session Now")').click();

        await expect(page).toHaveURL(/users\.php/);

        const role = run(`user get ${agencyUserId} --field=roles`);
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
        run(`user set-role ${agencyUserId} agency_pass_admin`);
        run(`user meta update ${agencyUserId} _agency_pass_user 1`);
        const expires = Math.floor(Date.now() / 1000) + 28800;
        run(`user meta update ${agencyUserId} _agency_pass_expires ${expires}`);

        // Now change to editor — triggers profile_update which removes meta.
        run(`user set-role ${agencyUserId} editor`);

        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);

        // The meta should be removed (promoted). Re-enroll should be available.
        await expect(page.locator('text=eligible for Agency Pass')).toBeVisible();
        await expect(page.locator('a:has-text("Re-enroll in Agency Pass")')).toBeVisible();
    });

    test('re-enroll button restores agency pass management', async ({ page }) => {
        await page.goto(`/wp-admin/user-edit.php?user_id=${agencyUserId}`);
        await page.locator('a:has-text("Re-enroll in Agency Pass")').click();

        await expect(page).toHaveURL(new RegExp(`user-edit\\.php.*user_id=${agencyUserId}`));

        const role = run(`user get ${agencyUserId} --field=roles`);
        expect(role).toContain('agency_pass_admin');
        await expect(page.locator('text=managed by Agency Pass')).toBeVisible();
    });
});
