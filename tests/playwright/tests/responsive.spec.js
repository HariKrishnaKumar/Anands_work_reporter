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
      
      if (vp.width < 768) {
        // MOBILE: bottom nav visible, no sidebar
        await expect(page.locator('.bottom-nav')).toBeVisible();
        await expect(page.locator('.nav-item-add')).toBeVisible();
        await expect(page.locator('.sidebar')).toBeHidden();
        
        // Navigate to add report via bottom nav
        await page.click('.nav-item-add');
      } else {
        // TABLET / DESKTOP: sidebar visible, bottom nav hidden
        await expect(page.locator('.sidebar')).toBeVisible();
        await expect(page.locator('.bottom-nav')).toBeHidden();
        await expect(page.locator('.app-header')).toBeHidden();
        
        // Sidebar nav items visible
        await expect(page.locator('.sidebar-nav-item').first()).toBeVisible();
        
        // Navigate to add report via sidebar
        await page.click('.sidebar-nav-item:nth-child(2)');
      }
      
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
