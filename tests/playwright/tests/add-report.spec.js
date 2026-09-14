// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TEST 3 — Add Work Report', () => {
  test('add report form has all required fields and no extras', async ({ page }) => {
    // Login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    
    // Click + button
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    // Title
    await expect(page.locator('h1')).toContainText('Add Today');
    
    // Work Date field exists
    await expect(page.locator('#work_date')).toBeVisible();
    await expect(page.locator('#work_date')).toHaveAttribute('type', 'date');
    
    // Description textarea exists
    await expect(page.locator('#description')).toBeVisible();
    await expect(page.locator('#description')).toHaveAttribute('placeholder', /Describe what you worked on/i);
    
    // Character counter exists
    await expect(page.locator('#charCounter')).toContainText('0/1000');
    
    // Upload area exists
    await expect(page.locator('.upload-area')).toBeVisible();
    await expect(page.locator('.upload-area')).toContainText('Tap to Upload');
    
    // Review button exists
    await expect(page.locator('button[type="submit"]')).toContainText('Review');
    
    // NO Priority field
    const priority = page.locator('text=Priority');
    await expect(priority).toHaveCount(0);
    
    // NO Category field
    const category = page.locator('text=Category');
    await expect(category).toHaveCount(0);
    
    // NO Status field
    const status = page.locator('text=Status');
    await expect(status).toHaveCount(0);
  });
});
