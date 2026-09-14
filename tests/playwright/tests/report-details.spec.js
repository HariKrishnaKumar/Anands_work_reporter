// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TEST 9-10 — Updated Home and Read-only Details', () => {
  test('saved report appears on home and details are read-only', async ({ page }) => {
    // Login via dev login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    
    // Add a report
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    const desc = 'Write comprehensive test suite for the daily work report application using Playwright.';
    await page.fill('#description', desc);
    await page.fill('#work_date', '2026-09-13');
    await page.locator('button[type="submit"]').scrollIntoViewIfNeeded();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/report/preview');
    
    // Save
    await page.click('#saveBtn');
    await page.click('#saveForm button[type="submit"]');
    await page.waitForURL('**/report/success', { timeout: 10000 });
    
    // Go to Home
    await page.click('text=Go to Home');
    await page.waitForURL('**/home');
    
    // TEST 9: New report appears at the top
    await expect(page.locator('.report-card').first()).toContainText('Write comprehensive test suite');
    
    // Click the report to view details
    await page.click('.report-card >> text=Write comprehensive test suite');
    await page.waitForURL(/\/report\/\d+/);
    
    // TEST 10: Read-only details
    await expect(page.locator('h1')).toContainText('Work Report');
    
    // Read Only badge
    await expect(page.locator('.readonly-badge')).toContainText('Read Only');
    
    // Date visible
    await expect(page.locator('.detail-value').first()).toContainText('13 Sep 2026');
    
    // Description visible
    await expect(page.locator('.detail-description')).toContainText('Write comprehensive test suite');
    
    // Read-only notice
    await expect(page.locator('text=cannot be edited')).toBeVisible();
    
    // NO Edit button
    const editBtn = page.locator('button:has-text("Edit"), a:has-text("Edit")');
    await expect(editBtn).toHaveCount(0);
    
    // NO Update button
    const updateBtn = page.locator('button:has-text("Update"), a:has-text("Update")');
    await expect(updateBtn).toHaveCount(0);
    
    // NO Delete button
    const deleteBtn = page.locator('button:has-text("Delete"), a:has-text("Delete")');
    await expect(deleteBtn).toHaveCount(0);
  });
});
