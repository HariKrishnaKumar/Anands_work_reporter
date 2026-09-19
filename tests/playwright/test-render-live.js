const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });
  const BASE = 'https://anands-work-reporter.onrender.com';
  const results = [];

  function log(test, status, detail) {
    const icon = status === 'PASS' ? '✅' : '❌';
    console.log(`${icon} ${test}: ${detail}`);
    results.push({ test, status, detail });
  }

  try {
    // Test 1: Health endpoint
    console.log('\n--- Health Check ---');
    const health = await page.goto(BASE + '/health', { timeout: 30000 });
    const healthJson = await page.textContent('body');
    const healthData = JSON.parse(healthJson);
    if (healthData.status === 'ok' && healthData.database === 'connected') {
      log('Health endpoint', 'PASS', 'Status: ok, Database: connected');
    } else {
      log('Health endpoint', 'FAIL', JSON.stringify(healthData));
    }

    // Test 2: Login page loads
    console.log('\n--- Login Page ---');
    await page.goto(BASE + '/login', { timeout: 30000, waitUntil: 'networkidle' });
    const title = await page.title();
    if (title.includes('Login')) {
      log('Login page loads', 'PASS', `Title: ${title}`);
    } else {
      log('Login page loads', 'FAIL', `Title: ${title}`);
    }

    // Test 3: Login form elements exist
    const emailInput = await page.$('#email');
    const passInput = await page.$('#password');
    const submitBtn = await page.$('button[type="submit"]');
    if (emailInput && passInput && submitBtn) {
      log('Login form elements', 'PASS', 'Email, password, submit button found');
    } else {
      log('Login form elements', 'FAIL', 'Missing form elements');
    }

    // Test 4: Quote text visible
    const quote = await page.$('#loginQuote');
    const quoteText = quote ? await quote.textContent() : null;
    if (quoteText && quoteText.length > 0) {
      log('Quote text', 'PASS', `Quote: "${quoteText}"`);
    } else {
      log('Quote text', 'FAIL', 'No quote text found');
    }

    // Test 5: Brand logo text visible
    const brandText = await page.$('.login-brand-logo span');
    const brandContent = brandText ? await brandText.textContent() : null;
    if (brandContent === 'Yajurvedh') {
      log('Brand text', 'PASS', `Brand: "${brandContent}"`);
    } else {
      log('Brand text', 'FAIL', `Brand: "${brandContent}"`);
    }

    // Test 6: Theme switcher exists
    const themeSwitch = await page.$('.login-theme-switcher');
    if (themeSwitch) {
      log('Theme switcher', 'PASS', 'Found');
    } else {
      log('Theme switcher', 'FAIL', 'Not found');
    }

    // Test 7: Login with valid credentials
    console.log('\n--- Login Flow ---');
    await page.goto(BASE + '/login', { timeout: 30000, waitUntil: 'networkidle' });
    await page.fill('#email', 'hari@test.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    
    const currentUrl = page.url();
    const bodyText = await page.textContent('body');
    if (bodyText && bodyText.includes('Table') && bodyText.includes("doesn't exist")) {
      log('Login auth (DB query)', 'FAIL', 'Database table missing - users table not seeded');
    } else if (currentUrl.includes('/home') || currentUrl.endsWith(BASE + '/') || currentUrl.endsWith(BASE)) {
      log('Login auth', 'PASS', `Redirected to: ${currentUrl}`);
    } else if (bodyText && bodyText.includes('Invalid')) {
      log('Login auth', 'FAIL', 'Invalid credentials');
    } else {
      log('Login auth', 'UNSURE', `URL: ${currentUrl}, Body preview: ${(bodyText || '').substring(0, 200)}`);
    }

    // Test 8: Screenshot login page
    console.log('\n--- Screenshots ---');
    await page.goto(BASE + '/login', { timeout: 30000, waitUntil: 'networkidle' });
    await page.screenshot({ path: 'screenshots/render-login-page.png', fullPage: true });
    log('Login screenshot', 'PASS', 'Saved to screenshots/render-login-page.png');

    // Test 9: Check dark mode theme on login
    const themeAttr = await page.getAttribute('html', 'data-theme');
    log('Theme detection', 'PASS', `Current theme: ${themeAttr}`);

    // Test 10: Check footer text
    const footerText = await page.$('.login-footer-text');
    const footerContent = footerText ? await footerText.textContent() : null;
    if (footerContent) {
      log('Footer text', 'PASS', `Footer: "${footerContent.trim()}"`);
    } else {
      log('Footer text', 'FAIL', 'Not found');
    }

    // Test 11: Check subtitle
    const subtitle = await page.$('.login-subtitle');
    const subtitleText = subtitle ? await subtitle.textContent() : null;
    if (subtitleText) {
      log('Subtitle', 'PASS', `Subtitle: "${subtitleText.trim()}"`);
    } else {
      log('Subtitle', 'FAIL', 'Not found');
    }

  } catch (err) {
    console.error('FATAL ERROR:', err.message);
  } finally {
    await browser.close();

    // Summary
    console.log('\n========== SUMMARY ==========');
    const passed = results.filter(r => r.status === 'PASS').length;
    const failed = results.filter(r => r.status === 'FAIL').length;
    const unsure = results.filter(r => r.status === 'UNSURE').length;
    console.log(`Total: ${results.length} | ✅ Passed: ${passed} | ❌ Failed: ${failed} | ⚠️ Unsure: ${unsure}`);
    results.filter(r => r.status === 'FAIL').forEach(r => console.log(`  ❌ ${r.test}: ${r.detail}`));
  }
})();
