// @ts-check
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: true,
  retries: 1,
  workers: 1,
  reporter: 'list',
  timeout: 60000,
  use: {
    baseURL: 'http://localhost/daily-work-report/public/',
    headless: false,
    slowMo: 150,
    screenshot: 'on',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'Mobile 320',   use: { viewport: { width: 320, height: 568 } } },
    { name: 'Mobile 390',   use: { viewport: { width: 390, height: 844 } } },
    { name: 'Mobile 414',   use: { viewport: { width: 414, height: 896 } } },
    { name: 'Tablet 768',   use: { viewport: { width: 768, height: 1024 } } },
    { name: 'Desktop 1366', use: { viewport: { width: 1366, height: 768 } } },
    { name: 'Desktop 1920', use: { viewport: { width: 1920, height: 1080 } } },
  ],
});
