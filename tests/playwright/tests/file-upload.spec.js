// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

test.describe('TEST 5 — File Upload Validation', () => {
  test('valid files upload and remove works', async ({ page }) => {
    // Login
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    await page.fill('#description', 'Testing file upload validation for various file types and sizes.');
    
    // Create test files
    const tmpDir = 'C:\\Users\\B_Hari_Krishna_Kumar\\AppData\\Local\\Temp';
    
    // Valid PNG
    const pngFile = path.join(tmpDir, 'test-image.png');
    fs.writeFileSync(pngFile, Buffer.from('fake png'));
    
    // Valid PDF
    const pdfFile = path.join(tmpDir, 'test-document.pdf');
    fs.writeFileSync(pdfFile, Buffer.from('%PDF-1.4 fake pdf'));
    
    // Upload valid files
    await page.locator('#fileInput').setInputFiles([pngFile, pdfFile]);
    
    // Both files should appear in the list
    await expect(page.locator('.file-item')).toHaveCount(2, { timeout: 5000 });
    await expect(page.locator('.file-item-name').first()).toContainText('test-image.png');
    await expect(page.locator('.file-item-name').nth(1)).toContainText('test-document.pdf');
    
    // File info visible (size)
    await expect(page.locator('.file-item-meta').first()).toBeVisible();
    
    // Remove one file
    await page.click('.file-item-remove >> nth=0');
    await expect(page.locator('.file-item')).toHaveCount(1);
    
    // Clean up
    fs.unlinkSync(pngFile);
    fs.unlinkSync(pdfFile);
  });

  test('file input accepts correct types', async ({ page }) => {
    await page.goto('dev-login');
    await page.waitForURL('**/home');
    await page.click('.nav-item-add');
    await page.waitForURL('**/report/add');
    
    const accept = await page.locator('#fileInput').getAttribute('accept');
    expect(accept).toContain('.jpg');
    expect(accept).toContain('.pdf');
    expect(accept).toContain('.doc');
    expect(accept).toContain('.docx');
    expect(accept).toContain('.png');
  });
});
