// @ts-check
const { test, expect } = require('@playwright/test');
const { clickAddReport } = require('./helpers');

test.describe('TEST 9-10 — Updated Home and Read-only Details', () => {
  test('saved report appears on home and details are read-only', async ({ page }) => {
    // Login via dev login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    
    // Add a report with unique description and today's date
    await clickAddReport(page);
    await page.waitForURL('**/report/add');
    
    const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
    const uniqueDesc = 'Unique report ' + Date.now() + ' - Playwright test for daily work report.';
    await page.fill('#description', uniqueDesc);
    await page.fill('#work_date', today);
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
    
    // TEST 9: New report appears — search for it by unique text
    await expect(page.locator('.report-card-desc', { hasText: 'Unique report' }).first()).toBeVisible({ timeout: 5000 });
    
    // Click the card that contains our report
    await page.locator('.report-card', { hasText: 'Unique report' }).first().click();
    await page.waitForURL(/\/report\/\d+/);
    
    // TEST 10: Read-only details
    await expect(page.locator('h1')).toContainText('Work Report');
    
    // Read Only badge
    await expect(page.locator('.readonly-badge')).toContainText('Read Only');
    
    // Description visible
    await expect(page.locator('.detail-description')).toContainText('Unique report');
    
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
