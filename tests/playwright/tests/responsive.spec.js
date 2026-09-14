// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TEST 13 — Responsive Testing', () => {
  const viewports = [
    { name: 'Mobile 390x844', width: 390, height: 844 },
    { name: 'Tablet 768x1024', width: 768, height: 1024 },
    { name: 'Desktop 1366x768', width: 1366, height: 768 },
  ];

  for (const vp of viewports) {
    test(`responsive at ${vp.name}`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
      });
      const page = await context.newPage();
      
      // Login
      await page.goto('dev-login');
      await page.waitForURL('**/home');
      
      // No horizontal overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(vp.width + 1);
      
      // + button visible
      await expect(page.locator('.nav-item-add')).toBeVisible();
      
      // Bottom nav visible
      await expect(page.locator('.bottom-nav')).toBeVisible();
      
      // Navigate to add report
      await page.click('.nav-item-add');
      await page.waitForURL('**/report/add');
      
      // No horizontal overflow on add page
      const addPageWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(addPageWidth).toBeLessThanOrEqual(vp.width + 1);
      
      // Controls accessible
      await expect(page.locator('#work_date')).toBeVisible();
      await expect(page.locator('#description')).toBeVisible();
      await expect(page.locator('.upload-area')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
      
      await context.close();
    });
  }
});
