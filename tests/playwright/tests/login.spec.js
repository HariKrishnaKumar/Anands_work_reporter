// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TEST 1 — Login Page', () => {
  test('login page loads correctly with no Google login', async ({ page }) => {
    await page.goto('login');
    
    // Page loads with correct title
    await expect(page).toHaveTitle(/Yajurvedh Work Report/i);
    
    // App name visible
    await expect(page.locator('.login-title')).toContainText('Yajurvedh Work Report');
    
    // Subtitle visible
    await expect(page.locator('.login-subtitle')).toContainText('Zoho');
    
    // Email and password fields exist
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    
    // Login button exists
    await expect(page.locator('button[type="submit"]')).toContainText('Login');
    
    // Dev Login button exists (dev environment)
    await expect(page.locator('.dev-login-btn')).toContainText('Dev Login');
    
    // NO Google login
    const googleLogin = page.locator('text=Google');
    await expect(googleLogin).toHaveCount(0);
    
    // NO Google logo
    const googleLogo = page.locator('text=Continue with Google');
    await expect(googleLogo).toHaveCount(0);
  });
});
