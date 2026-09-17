// @ts-check
const { test, expect } = require('@playwright/test');

const breakpoints = [
  { name: '320x568', width: 320, height: 568 },
  { name: '390x844', width: 390, height: 844 },
  { name: '412x924', width: 412, height: 924 },
  { name: '414x896', width: 414, height: 896 },
];

test.describe('UI QA — Mobile Breakpoints', () => {
  for (const bp of breakpoints) {
    test(`header + theme switcher + bottom nav at ${bp.name}`, async ({ page }) => {
      await page.setViewportSize({ width: bp.width, height: bp.height });

      // Login
      await page.goto('dev-login');
      await page.waitForURL('**/home');
      await page.waitForTimeout(800);

      // Screenshot 1: Home (dark)
      await page.screenshot({ path: `screenshots/home-dark-${bp.name}.png`, fullPage: false });

      // Theme switcher visible in header
      const themeSwitcher = page.locator('.app-header .theme-switcher');
      await expect(themeSwitcher).toBeVisible();
      const switcherBox = await themeSwitcher.boundingBox();
      expect(switcherBox).not.toBeNull();
      expect(switcherBox.width).toBeGreaterThanOrEqual(50);
      expect(switcherBox.height).toBeGreaterThanOrEqual(28);

      // Logout visible and not overlapping
      const logout = page.locator('.app-header .logout-link');
      await expect(logout).toBeVisible();
      const logoutBox = await logout.boundingBox();
      expect(logoutBox).not.toBeNull();
      expect(logoutBox.x).toBeGreaterThan(switcherBox.x + switcherBox.width - 2);

      // Bottom nav visible
      const bottomNav = page.locator('.bottom-nav');
      await expect(bottomNav).toBeVisible();

      // Nav pill bg exists
      await expect(page.locator('.nav-pill-bg')).toBeAttached();

      // Home active
      await expect(page.locator('.nav-item.active:has-text("Home")')).toBeVisible();

      // Add NOT active
      const addIsActive = await page.locator('.nav-item:has-text("Add")').evaluate(el => el.classList.contains('active'));
      expect(addIsActive).toBe(false);

      // Toggle to light via JS (reliable, avoids click timing)
      await page.evaluate(() => {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
      });
      await page.waitForTimeout(300);

      // Screenshot 2: Home (light)
      await page.screenshot({ path: `screenshots/home-light-${bp.name}.png`, fullPage: false });

      // Toggle back to dark
      await page.evaluate(() => {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
      });
      await page.waitForTimeout(300);

      // Navigate to Add
      await page.locator('.nav-item:has-text("Add")').click();
      await page.waitForURL('**/report/add');
      await page.waitForTimeout(800);

      // Screenshot 3: Add page
      await page.screenshot({ path: `screenshots/add-${bp.name}.png`, fullPage: false });

      // Add is now active
      await expect(page.locator('.nav-item.active:has-text("Add")')).toBeVisible();

      // Home NOT active
      const homeIsActive = await page.locator('.nav-item:has-text("Home")').evaluate(el => el.classList.contains('active'));
      expect(homeIsActive).toBe(false);

      // Navigate back to Home
      await page.locator('.nav-item:has-text("Home")').click();
      await page.waitForURL('**/home');
      await page.waitForTimeout(800);

      // Screenshot 4: Back to Home
      await page.screenshot({ path: `screenshots/home-return-${bp.name}.png`, fullPage: false });

      // Home active again
      await expect(page.locator('.nav-item.active:has-text("Home")')).toBeVisible();

      // No horizontal overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(bp.width + 1);
    });
  }
});
