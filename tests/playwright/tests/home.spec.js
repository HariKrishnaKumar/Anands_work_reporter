// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TEST 2 — Home / My Reports', () => {
  test('home shows user greeting, reports, and navigation', async ({ page }) => {
    // Login via dev login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    
    // Greeting with user name
    await expect(page.locator('.greeting-name')).toContainText('Hari Krishna');
    
    // Current date
    await expect(page.locator('.greeting-date')).toBeVisible();
    
    // Greeting subtitle
    await expect(page.locator('.greeting-subtitle')).toContainText('submitted work');
    
    // Navigation exists (bottom nav on mobile, sidebar on tablet+)
    const viewportWidth = page.viewportSize()?.width || 390;
    if (viewportWidth < 768) {
      await expect(page.locator('.bottom-nav')).toBeVisible();
      await expect(page.locator('.nav-item-add')).toBeVisible();
    } else {
      await expect(page.locator('.sidebar')).toBeVisible();
      await expect(page.locator('.sidebar-nav-item').first()).toBeVisible();
    }
    
    // Quick action card exists
    await expect(page.locator('.quick-action')).toContainText('Add Today');
    
    // NO Profile navigation
    const profileNav = page.locator('text=Profile');
    await expect(profileNav).toHaveCount(0);
  });

  test('home shows empty state when no reports', async ({ page }) => {
    // Login as priya (no reports)
    await page.goto('login');
    await page.fill('#email', 'priya@test.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/home');
    
    // Empty state
    await expect(page.locator('.empty-state')).toBeVisible();
    await expect(page.locator('.empty-state')).toContainText('No work reports yet');
  });
});
