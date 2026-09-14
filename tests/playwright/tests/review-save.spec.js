// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');

test.describe('TEST 4-6 — Full Add/Review/Save Flow', () => {
  test('complete add-report-save-success flow', async ({ page }) => {
    // Login via dev login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    
    // Click + to add report
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    // TEST 4: Enter description
    const description = 'Prepare project documentation and review code changes for the team meeting tomorrow. Updated API endpoints and fixed authentication bugs.';
    await page.fill('#description', description);
    
    // Set a specific date
    await page.fill('#work_date', '2026-09-13');
    
    // TEST 5: Upload a real file from sample directory
    const sampleFile = 'C:\\Users\\B_Hari_Krishna_Kumar\\Documents\\Anand\\employee task\\daily-work-report\\sample\\IMAGE.jpeg';
    
    await page.locator('#fileInput').setInputFiles(sampleFile);
    
    // File should appear in the file list
    await expect(page.locator('.file-item')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('.file-item-name')).toContainText('IMAGE');
    
    // Remove button exists
    await expect(page.locator('.file-item-remove')).toBeVisible();
    
    // TEST 6: Click Review — scroll into view first to clear bottom nav
    await page.locator('button[type="submit"]').scrollIntoViewIfNeeded();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/report/preview');
    
    // Review page shows all data
    await expect(page.locator('h1')).toContainText('Review Work Report');
    await expect(page.locator('.review-value').first()).toContainText('13 Sep 2026');
    await expect(page.locator('.review-value').nth(1)).toContainText('Prepare project documentation');
    
    // Save button exists
    await expect(page.locator('#saveBtn')).toContainText('Save Work Report');
  });
});

test.describe('TEST 7 — Confirmation Modal', () => {
  test('confirmation modal works correctly', async ({ page }) => {
    // Setup: login and get to review page
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    await page.fill('#description', 'Test confirmation flow - writing tests and fixing bugs in the application codebase.');
    await page.fill('#work_date', '2026-09-13');
    await page.locator('button[type="submit"]').scrollIntoViewIfNeeded();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/report/preview');
    
    // Modal is hidden initially
    await expect(page.locator('#confirmModal')).not.toBeVisible();
    
    // Click Save button to show modal
    await page.click('#saveBtn');
    await expect(page.locator('#confirmModal')).toBeVisible();
    await expect(page.locator('.modal-title')).toContainText('Save Work Report');
    
    // Cancel goes back to review
    await page.click('#cancelBtn');
    await expect(page.locator('#confirmModal')).not.toBeVisible();
    
    // Still on review page
    await expect(page.locator('h1')).toContainText('Review Work Report');
    
    // Now actually save
    await page.click('#saveBtn');
    await expect(page.locator('#confirmModal')).toBeVisible();
    
    // Click Save in the modal
    await page.click('#saveForm button[type="submit"]');
    
    // TEST 8: Success page
    await page.waitForURL('**/report/success', { timeout: 10000 });
    await expect(page.locator('.success-title')).toContainText('Work Report Saved');
    await expect(page.locator('.success-text')).toContainText('recorded successfully');
    await expect(page.locator('text=Go to Home')).toBeVisible();
    await expect(page.locator('text=Add Another Report')).toBeVisible();
  });
});
