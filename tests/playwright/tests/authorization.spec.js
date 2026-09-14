// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const { clickAddReport } = require('./helpers');

test.describe('TEST 11 — User Authorization', () => {
  test('User A cannot see User B reports', async ({ page }) => {
    // Login as User A (Hari) and create a report
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    await clickAddReport(page);
    await page.waitForURL('**/report/add');
    await page.fill('#description', 'Hari report - authorization test for user isolation between different users.');
    await page.fill('#work_date', '2026-09-13');
    await page.locator('button[type="submit"]').scrollIntoViewIfNeeded();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/report/preview');
    await page.click('#saveBtn');
    await page.click('#saveForm button[type="submit"]');
    await page.waitForURL('**/report/success', { timeout: 10000 });
    
    // Go to home to get report link
    await page.click('text=Go to Home');
    await page.waitForURL('**/home');
    
    // Get Hari's report link
    const hariReportLink = await page.locator('.report-card').first().getAttribute('href');
    const reportId = hariReportLink?.match(/\/report\/(\d+)/)?.[1];
    expect(reportId).toBeTruthy();
    
    // Logout
    await page.goto('logout');
    await page.waitForURL('**/login');
    
    // Login as User B (Anand)
    await page.fill('#email', 'anand@test.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/home');
    
    // Anand should NOT see Hari's report
    await expect(page.locator('.report-card >> text=Hari report')).toHaveCount(0);
    
    // Try to access Hari's report directly
    await page.goto(`report/${reportId}`);
    // Should get not-found page
    const bodyText = await page.textContent('body');
    const isNotFound = bodyText?.includes('not found') || bodyText?.includes('does not exist');
    expect(isNotFound).toBeTruthy();
  });
});

test.describe('TEST 12 — File Authorization', () => {
  test('User cannot access another user files', async ({ page }) => {
    // Login as Hari and create a report with a file
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    await clickAddReport(page);
    await page.waitForURL('**/report/add');
    
    await page.fill('#description', 'Hari file test - testing file access authorization between users in the system.');
    await page.fill('#work_date', '2026-09-13');
    
    // Upload a real test file from sample directory
    const sampleFile = 'C:\\Users\\B_Hari_Krishna_Kumar\\Documents\\Anand\\employee task\\daily-work-report\\sample\\IMAGE.jpeg';
    
    await page.locator('#fileInput').setInputFiles(sampleFile);
    await expect(page.locator('.file-item')).toBeVisible({ timeout: 5000 });
    
    await page.locator('button[type="submit"]').scrollIntoViewIfNeeded();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/report/preview');
    await page.click('#saveBtn');
    await page.click('#saveForm button[type="submit"]');
    await page.waitForURL('**/report/success', { timeout: 10000 });
    
    // Go to home and view the report
    await page.click('text=Go to Home');
    await page.waitForURL('**/home');
    await page.click('.report-card >> text=Hari file test');
    await page.waitForURL(/\/report\/\d+/);
    
    // Find file link
    const fileLink = await page.locator('a[href*="/files/"]').first().getAttribute('href');
    const fileId = fileLink?.match(/\/files\/(\d+)/)?.[1];
    
    // Logout and login as Anand
    await page.goto('logout');
    await page.waitForURL('**/login');
    await page.fill('#email', 'anand@test.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/home');
    
    // Try to access Hari's file
    if (fileId) {
      await page.goto(`files/${fileId}`);
      const bodyText = await page.textContent('body');
      const denied = bodyText?.includes('not found') || bodyText?.includes('File not found') || bodyText?.includes('denied');
      expect(denied).toBeTruthy();
    }
  });
});
