// @ts-check
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASE = 'http://localhost/daily-work-report/public';
const SS_DIR = path.join(__dirname, '..', '.gstack', 'qa-reports', 'screenshots');

// Collect all issues
const issues = [];
let issueNum = 0;

function logIssue(severity, title, page, repro, evidence) {
  issueNum++;
  issues.push({ num: issueNum, severity, title, page, repro, evidence });
  console.log(`[ISSUE-${String(issueNum).padStart(3,'0')}] [${severity}] ${title}`);
}

test.describe('GStack QA — Full App Audit', () => {

  // ========== PHASE 3: ORIENT ==========
  test('Phase 3: Orient — Landing page + console errors + links', async ({ page }) => {
    const errors = [];
    page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
    page.on('pageerror', err => errors.push(err.message));

    await page.goto(`${BASE}/login`);
    await page.screenshot({ path: path.join(SS_DIR, '01-login-landing.jpg'), fullPage: true });

    // Check page loaded
    await expect(page.locator('body')).toBeVisible();
    console.log(`LOGIN_PAGE_LOADED: ${await page.title()}`);

    // Check for JS errors
    if (errors.length > 0) {
      logIssue('HIGH', 'Console errors on login page', '/login',
        'Navigate to /login', `Console errors: ${errors.join('; ')}`);
    }
    console.log(`CONSOLE_ERRORS=${JSON.stringify(errors)}`);

    // Map all links
    const links = await page.evaluate(() =>
      [...document.querySelectorAll('a[href]')].map(a => ({
        text: a.textContent.trim(),
        href: a.href
      }))
    );
    console.log(`LINKS_FOUND: ${links.length}`);
    links.forEach(l => console.log(`LINK ${l.text} → ${l.href}`));
  });

  // ========== PHASE 4: EXPLORE — Login Page ==========
  test('Phase 4: Login page — form validation', async ({ page }) => {
    await page.goto(`${BASE}/login`);

    // Test empty submit
    const submitBtn = page.locator('button[type="submit"], input[type="submit"], .btn-primary').first();
    if (await submitBtn.isVisible()) {
      await submitBtn.click();
      await page.waitForLoadState('networkidle');
      const flashError = page.locator('.flash, .flash-error, [class*="flash"]');
      if (await flashError.isVisible({ timeout: 3000 }).catch(() => false)) {
        console.log('EMPTY_SUBMIT: Shows error (good)');
      } else {
        logIssue('MEDIUM', 'No validation error on empty login', '/login',
          'Click submit with empty fields', 'No flash message shown');
      }
    }

    // Test invalid credentials
    await page.goto(`${BASE}/login`);
    const emailField = page.locator('input[name="email"], input[type="email"]').first();
    const passField = page.locator('input[name="password"], input[type="password"]').first();
    if (await emailField.isVisible() && await passField.isVisible()) {
      await emailField.fill('wrong@test.com');
      await passField.fill('wrongpassword');
      await submitBtn.click();
      await page.waitForLoadState('networkidle');
      await page.screenshot({ path: path.join(SS_DIR, '02-login-invalid.jpg'), fullPage: true });
      console.log('INVALID_LOGIN: Tested');
    }

    // Test valid login
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await submitBtn.click();
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(SS_DIR, '03-after-login.jpg'), fullPage: true });
    console.log(`POST_LOGIN_URL=${page.url()}`);
  });

  // ========== PHASE 4: EXPLORE — Home Page ==========
  test('Phase 4: Home page — greeting, empty state, navigation', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await page.locator('button[type="submit"], input[type="submit"], .btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Home page
    await page.goto(`${BASE}/home`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(SS_DIR, '04-home-page.jpg'), fullPage: true });

    // Check greeting
    const bodyText = await page.textContent('body');
    const hasGreeting = /good\s+(morning|afternoon|evening)/i.test(bodyText);
    console.log(`GREETING_PRESENT: ${hasGreeting}`);
    if (!hasGreeting) {
      logIssue('LOW', 'No personalized greeting on home', '/home',
        'Login and visit /home', 'Body text has no Good Morning/Afternoon/Evening');
    }

    // Check empty state
    const hasEmptyState = /no\s+(reports|work)|empty|start\s+by/i.test(bodyText);
    console.log(`EMPTY_STATE: ${hasEmptyState}`);

    // Check navigation elements
    const bottomNav = page.locator('.bottom-nav, .nav-bar, nav');
    const bottomNavVisible = await bottomNav.isVisible({ timeout: 3000 }).catch(() => false);
    console.log(`BOTTOM_NAV: ${bottomNavVisible}`);

    // Check theme switcher
    const themeSwitch = page.locator('.theme-switcher, [class*="theme"], .dark-mode-toggle');
    const themeVisible = await themeSwitch.first().isVisible({ timeout: 3000 }).catch(() => false);
    console.log(`THEME_SWITCHER: ${themeVisible}`);
  });

  // ========== PHASE 4: EXPLORE — Add Report Flow ==========
  test('Phase 4: Add report — form, preview, save flow', async ({ page }) => {
    // Login
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await page.locator('button[type="submit"], input[type="submit"], .btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Navigate to add report
    await page.goto(`${BASE}/report/add`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(SS_DIR, '05-add-report-form.jpg'), fullPage: true });

    // Check form fields exist
    const dateField = page.locator('input[name="work_date"], input[type="date"]').first();
    const descField = page.locator('textarea[name="description"], textarea').first();
    console.log(`DATE_FIELD: ${await dateField.isVisible()}`);
    console.log(`DESC_FIELD: ${await descField.isVisible()}`);

    // Test validation — short description
    await dateField.fill('2026-09-17');
    await descField.fill('Short');
    const reviewBtn = page.locator('button:has-text("Review"), button:has-text("Preview"), input[type="submit"]').first();
    if (await reviewBtn.isVisible()) {
      await reviewBtn.click();
      await page.waitForLoadState('networkidle');
      const flashError = page.locator('.flash-error, .flash, [class*="flash"]');
      const hasError = await flashError.isVisible({ timeout: 3000 }).catch(() => false);
      console.log(`SHORT_DESC_VALIDATION: ${hasError}`);
      if (!hasError) {
        logIssue('MEDIUM', 'No validation for short description', '/report/add',
          'Enter description < 10 chars and submit', 'No error message shown');
      }
    }

    // Test valid submission → preview
    await page.goto(`${BASE}/report/add`);
    await page.locator('input[name="work_date"], input[type="date"]').first().fill('2026-09-17');
    await page.locator('textarea[name="description"], textarea').first().fill('Working on QA testing for the daily work report application. Testing all features thoroughly.');
    await page.screenshot({ path: path.join(SS_DIR, '06-add-report-filled.jpg'), fullPage: true });

    const submitBtn = page.locator('button:has-text("Review"), button:has-text("Preview"), input[type="submit"]').first();
    await submitBtn.click();
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(SS_DIR, '07-review-page.jpg'), fullPage: true });
    console.log(`REVIEW_URL=${page.url()}`);

    // Check review page has data
    const reviewText = await page.textContent('body');
    const hasDate = reviewText.includes('2026') || reviewText.includes('17');
    const hasDesc = reviewText.includes('QA testing');
    console.log(`REVIEW_HAS_DATE: ${hasDate}`);
    console.log(`REVIEW_HAS_DESC: ${hasDesc}`);

    // Save
    const saveBtn = page.locator('button:has-text("Save"), button:has-text("Confirm"), .btn-primary').last();
    if (await saveBtn.isVisible()) {
      await saveBtn.click();
      await page.waitForLoadState('networkidle');
      await page.screenshot({ path: path.join(SS_DIR, '08-success-page.jpg'), fullPage: true });
      console.log(`SUCCESS_URL=${page.url()}`);
      const successText = await page.textContent('body');
      const hasSuccess = /saved|success|recorded/i.test(successText);
      console.log(`SUCCESS_MESSAGE: ${hasSuccess}`);
      if (!hasSuccess) {
        logIssue('HIGH', 'No success message after saving report', '/report/success',
          'Complete add→review→save flow', 'Page does not show success confirmation');
      }
    }
  });

  // ========== PHASE 4: EXPLORE — View Report ==========
  test('Phase 4: View report — detail page', async ({ page }) => {
    // Login
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await page.locator('button[type="submit"], input[type="submit"], .btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Go to home and click on a report
    await page.goto(`${BASE}/home`);
    await page.waitForLoadState('networkidle');

    const reportLink = page.locator('a[href*="/report/"]').first();
    if (await reportLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await reportLink.click();
      await page.waitForLoadState('networkidle');
      await page.screenshot({ path: path.join(SS_DIR, '09-report-detail.jpg'), fullPage: true });
      console.log(`DETAIL_URL=${page.url()}`);

      const detailText = await page.textContent('body');
      console.log(`DETAIL_HAS_DATE: ${/\\d{4}-\\d{2}-\\d{2}|\\d{1,2}\\s\\w+\\s\\d{4}/.test(detailText)}`);
      console.log(`DETAIL_HAS_DESC: ${detailText.length > 100}`);
    } else {
      console.log('NO_REPORTS_LINKED: No reports to view (expected on clean DB)');
    }
  });

  // ========== PHASE 4: EXPLORE — Authorization ==========
  test('Phase 4: Authorization — user isolation', async ({ page }) => {
    // Login as hari
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await page.locator('button[type="submit"], input[type="submit"], .btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    // Try accessing report ID 999 (doesn't exist)
    await page.goto(`${BASE}/report/999`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(SS_DIR, '10-not-found.jpg'), fullPage: true });
    const notFoundText = await page.textContent('body');
    const shows404 = /not\s+found|404|doesn't\s+exist/i.test(notFoundText);
    console.log(`404_HANDLING: ${shows404}`);
    if (!shows404) {
      logIssue('MEDIUM', 'No 404 page for non-existent report', '/report/999',
        'Navigate to /report/999', 'No not-found message shown');
    }

    // Logout
    const logoutLink = page.locator('a[href*="logout"], button:has-text("Logout")').first();
    if (await logoutLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      await logoutLink.click();
      await page.waitForLoadState('networkidle');
    }

    // Try accessing home without auth
    await page.goto(`${BASE}/home`);
    await page.waitForLoadState('networkidle');
    const redirectedToLogin = page.url().includes('login');
    console.log(`UNAUTH_REDIRECT: ${redirectedToLogin}`);
    if (!redirectedToLogin) {
      logIssue('CRITICAL', 'Unauthenticated access to /home allowed', '/home',
        'Logout and navigate to /home', 'Page loads without redirect to login');
    }
  });

  // ========== PHASE 4: EXPLORE — File Upload ==========
  test('Phase 4: File upload — validation and preview', async ({ page }) => {
    // Login
    await page.goto(`${BASE}/login`);
    await page.locator('input[name="email"], input[type="email"]').first().fill('hari@test.com');
    await page.locator('input[name="password"], input[type="password"]').first().fill('password123');
    await page.locator('button[type="submit"], input[type="submit"], .btn-primary').first().click();
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/report/add`);
    await page.waitForLoadState('networkidle');

    // Check file upload area exists
    const fileInput = page.locator('input[type="file"]');
    const hasFileUpload = await fileInput.isVisible({ timeout: 3000 }).catch(() => false);
    console.log(`FILE_UPLOAD_AREA: ${hasFileUpload}`);

    if (hasFileUpload) {
      // Upload a test image
      const samplePath = path.join(__dirname, '..', 'sample', 'IMAGE.jpeg');
      if (fs.existsSync(samplePath)) {
        await fileInput.setInputFiles(samplePath);
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(SS_DIR, '11-file-uploaded.jpg'), fullPage: true });
        console.log('FILE_UPLOAD: Image uploaded');
      }
    }
  });

  // ========== PHASE 4: EXPLORE — Theme Toggle ==========
  test('Phase 4: Theme toggle — dark/light mode', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');

    // Check default theme (should be dark)
    const htmlClass = await page.evaluate(() => document.documentElement.className || document.body.className);
    console.log(`DEFAULT_THEME: ${htmlClass || 'none'}`);
    await page.screenshot({ path: path.join(SS_DIR, '12-login-dark.jpg'), fullPage: true });

    // Find and click theme switcher
    const themeBtn = page.locator('.theme-switcher button, .theme-toggle, [class*="theme"] button, button[aria-label*="theme"]').first();
    if (await themeBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await themeBtn.click();
      await page.waitForTimeout(500);
      await page.screenshot({ path: path.join(SS_DIR, '13-login-light.jpg'), fullPage: true });
      const newClass = await page.evaluate(() => document.documentElement.className || document.body.className);
      console.log(`AFTER_TOGGLE: ${newClass || 'none'}`);
    } else {
      logIssue('LOW', 'Theme switcher not found on login', '/login',
        'Look for theme toggle button', 'No visible theme switcher element');
    }
  });

  // ========== PHASE 4: EXPLORE — Responsive ==========
  test('Phase 4: Responsive — mobile viewport', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');

    // Mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(SS_DIR, '14-login-mobile.jpg'), fullPage: true });

    // Check mobile layout
    const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
    const viewportWidth = 375;
    const hasHorizontalScroll = bodyWidth > viewportWidth + 5;
    console.log(`MOBILE_OVERFLOW: ${hasHorizontalScroll} (body=${bodyWidth}, viewport=${viewportWidth})`);
    if (hasHorizontalScroll) {
      logIssue('MEDIUM', 'Horizontal scroll on mobile', '/login',
        'View login at 375px width', `Body width ${bodyWidth}px exceeds viewport ${viewportWidth}px`);
    }

    // Check if elements are touch-friendly (min 44px tap targets)
    const buttons = await page.locator('button, a.btn, input[type="submit"]').all();
    let smallTargets = 0;
    for (const btn of buttons) {
      const box = await btn.boundingBox();
      if (box && (box.width < 44 || box.height < 30)) smallTargets++;
    }
    if (smallTargets > 0) {
      logIssue('LOW', `Small tap targets on mobile (${smallTargets} buttons)`, '/login',
        'Check button sizes at 375px', `${smallTargets} buttons below 44px minimum`);
    }
    console.log(`SMALL_TAP_TARGETS: ${smallTargets}`);
  });

  // ========== PHASE 6: SECURITY CHECKS ==========
  test('Phase 6: Security — CSRF, headers, injection', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');

    // Check CSRF token exists
    const csrfToken = await page.evaluate(() => {
      const input = document.querySelector('input[name="csrf_token"]');
      return input ? input.value : null;
    });
    console.log(`CSRF_TOKEN: ${csrfToken ? 'present' : 'MISSING'}`);
    if (!csrfToken) {
      logIssue('CRITICAL', 'Missing CSRF token on login form', '/login',
        'Check for hidden csrf_token input', 'No CSRF token found in form');
    }

    // Check response headers
    const response = await page.goto(`${BASE}/login`);
    const headers = response.headers();
    console.log(`X_FRAME_OPTIONS: ${headers['x-frame-options'] || 'not set'}`);
    console.log(`CONTENT_SECURITY_POLICY: ${headers['content-security-policy'] || 'not set'}`);
    console.log(`X_CONTENT_TYPE_OPTIONS: ${headers['x-content-type-options'] || 'not set'}`);

    if (!headers['x-frame-options']) {
      logIssue('MEDIUM', 'Missing X-Frame-Options header', '/login',
        'Check response headers', 'No X-Frame-Options — clickjacking possible');
    }
    if (!headers['content-security-policy']) {
      logIssue('MEDIUM', 'Missing Content-Security-Policy header', '/login',
        'Check response headers', 'No CSP header — XSS risk');
    }
  });

  // ========== PHASE 6: PERFORMANCE ==========
  test('Phase 6: Performance — load times and page weight', async ({ page }) => {
    // Measure login page load
    const start = Date.now();
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    const loadTime = Date.now() - start;
    console.log(`LOGIN_LOAD_TIME: ${loadTime}ms`);
    if (loadTime > 3000) {
      logIssue('MEDIUM', `Slow login page load (${loadTime}ms)`, '/login',
        'Navigate to /login', `Page took ${loadTime}ms to load`);
    }

    // Count DOM elements
    const domCount = await page.evaluate(() => document.querySelectorAll('*').length);
    console.log(`DOM_ELEMENTS: ${domCount}`);
    if (domCount > 1500) {
      logIssue('LOW', `Heavy DOM (${domCount} elements)`, '/login',
        'Check DOM size', 'Large DOM may impact mobile performance');
    }

    // Check for render-blocking resources
    const resources = await page.evaluate(() => {
      return performance.getEntriesByType('resource').map(r => ({
        name: r.name.split('/').pop(),
        duration: Math.round(r.duration),
        size: r.transferSize
      }));
    });
    console.log(`RESOURCES_LOADED: ${resources.length}`);
    resources.forEach(r => console.log(`RESOURCE ${r.name}: ${r.duration}ms, ${r.size}B`));
  });
});
