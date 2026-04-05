const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    globalSetup: './e2e/global-setup.js',
    testDir: './e2e',
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    use: {
        baseURL: process.env.WP_BASE_URL || 'https://agency-pass.ddev.site',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /.*\.setup\.js/ },
        {
            name: 'e2e',
            dependencies: ['setup'],
            use: { storageState: '.auth/admin.json' },
        },
    ],
});
