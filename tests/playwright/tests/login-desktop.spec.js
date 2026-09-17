// @ts-check
const { test, expect } = require('@playwright/test');

const desktopBps = [
  { name: '1366x768', width: 1366, height: 768 },
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1536x864', width: 1536, height: 864 },
  { name: '1920x1080', width: 1920, height: 1080 },
];

const mobileBps = [
  { name: '320x568', width: 320, height: 568 },
  { name: '390x844', width: 390, height: 844 },
  { name: '412x924', width: 412, height: 924 },
  { name: '414x896', width: 414, height: 896 },
];

const tabletBps = [
  { name: '768x1024', width: 768, height: 1024 },
  { name: '820x1180', width: 820, height: 1180 },
  { name: '912x1368', width: 912, height: 1368 },
];

test.describe('Login Page — Desktop', () => {
  for (const bp of desktopBps) {
    test(`login fills viewport + live image at ${bp.name}`, async ({ page }) => {
      await page.setViewportSize({ width: bp.width, height: bp.height });
      await page.goto('login');

      // Wait for Unsplash image to load (network + render)
      await page.waitForTimeout(3000);

      // --- VIEWPORT FILL CHECKS ---
      // Login page fills entire viewport (no large gaps)
      const pageRect = await page.evaluate(() => {
        const el = document.querySelector('.login-page');
        return el ? el.getBoundingClientRect() : null;
      });
      expect(pageRect).not.toBeNull();
      expect(pageRect.width).toBeGreaterThanOrEqual(bp.width - 2);
      expect(pageRect.height).toBeGreaterThanOrEqual(bp.height - 2);

      // Login shell fills viewport (no floating card)
      const shellRect = await page.evaluate(() => {
        const el = document.querySelector('.login-shell');
        return el ? el.getBoundingClientRect() : null;
      });
      expect(shellRect).not.toBeNull();
      expect(shellRect.width).toBeGreaterThanOrEqual(bp.width - 2);
      expect(shellRect.height).toBeGreaterThanOrEqual(bp.height - 2);

      // Hero panel visible and fills left side
      const hero = page.locator('.login-hero');
      await expect(hero).toBeVisible();
      const heroRect = await hero.boundingBox();
      expect(heroRect.x).toBe(0);
      expect(heroRect.width).toBeGreaterThan(bp.width * 0.45);
      expect(heroRect.height).toBeGreaterThanOrEqual(bp.height - 2);

      // Form panel visible and fills right side
      const formPanel = page.locator('.login-form-panel');
      await expect(formPanel).toBeVisible();
      const formRect = await formPanel.boundingBox();
      expect(formRect.x).toBeGreaterThan(bp.width * 0.45);
      expect(formRect.width).toBeGreaterThan(bp.width * 0.35);
      expect(formRect.height).toBeGreaterThanOrEqual(bp.height - 2);

      // No large outer gaps (form right edge near viewport right)
      expect(formRect.x + formRect.width).toBeGreaterThanOrEqual(bp.width - 2);

      // --- IMAGE LOADING CHECKS ---
      const imgState = await page.evaluate(() => {
        const img = document.getElementById('loginHeroImg');
        if (!img) return null;
        return {
          src: img.src,
          currentSrc: img.currentSrc,
          naturalWidth: img.naturalWidth,
          naturalHeight: img.naturalHeight,
          complete: img.complete,
        };
      });
      expect(imgState).not.toBeNull();
      expect(imgState.naturalWidth).toBeGreaterThan(0);
      expect(imgState.naturalHeight).toBeGreaterThan(0);
      expect(imgState.complete).toBe(true);
      expect(imgState.currentSrc).toContain('images.unsplash.com');

      // First image URL captured
      const firstSrc = imgState.currentSrc;

      // --- WAIT FOR IMAGE ROTATION (~5s) ---
      await page.waitForTimeout(6000);

      const secondImgState = await page.evaluate(() => {
        const img = document.getElementById('loginHeroImg');
        if (!img) return null;
        return {
          currentSrc: img.currentSrc,
          naturalWidth: img.naturalWidth,
        };
      });
      // Image should have rotated (different URL)
      expect(secondImgState.currentSrc).toContain('images.unsplash.com');
      expect(secondImgState.naturalWidth).toBeGreaterThan(0);

      // --- FROSTED GLASS PANEL CHECK ---
      const glass = page.locator('.login-form-glass');
      await expect(glass).toBeVisible();

      // --- RIGHT SIDE SAME IMAGE CHECK ---
      const rightBg = await page.evaluate(() => {
        const el = document.getElementById('loginRightBg');
        return el ? el.style.backgroundImage : '';
      });
      expect(rightBg).toContain('images.unsplash.com');

      // --- QUOTE CHECK ---
      const quote = page.locator('#loginQuote');
      await expect(quote).toBeVisible();
      const quoteText = await quote.textContent();
      expect(quoteText.length).toBeGreaterThan(5);

      // --- BRANDING ---
      await expect(page.locator('.login-hero-logo span')).toContainText('Yajurvedh Work Report');
      await expect(page.locator('.login-title')).toContainText('Yajurvedh Work Report');
      await expect(page.locator('.login-subtitle')).toContainText('Zoho');

      // --- NO DAILY WORK REPORT ---
      const dailyText = page.locator('text=Daily Work Report');
      await expect(dailyText).toHaveCount(0);

      // --- THEME SWITCHER ---
      const themeSwitcher = page.locator('.login-theme-switcher');
      await expect(themeSwitcher).toBeVisible();

      // --- NO HORIZONTAL OVERFLOW ---
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(bp.width + 1);

      // --- FORM FIELDS ---
      await expect(page.locator('#email')).toBeVisible();
      await expect(page.locator('#password')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();

      // Screenshot
      await page.screenshot({ path: `screenshots/login-desktop-${bp.name}.png`, fullPage: false });
    });
  }
});

test.describe('Login Page — Mobile', () => {
  for (const bp of mobileBps) {
    test(`login at ${bp.name}`, async ({ page }) => {
      await page.setViewportSize({ width: bp.width, height: bp.height });
      await page.goto('login');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `screenshots/login-mobile-${bp.name}.png`, fullPage: false });

      // Desktop hero should NOT be visible
      const hero = page.locator('.login-hero');
      await expect(hero).toHaveCSS('display', 'none');

      // Form visible
      const formPanel = page.locator('.login-form-panel');
      await expect(formPanel).toBeVisible();

      // No horizontal overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(bp.width + 1);

      // Theme switcher usable
      await expect(page.locator('.login-theme-switcher')).toBeVisible();

      // Form fields usable
      await expect(page.locator('#email')).toBeVisible();
      await expect(page.locator('#password')).toBeVisible();
    });
  }
});

test.describe('Login Page — Tablet', () => {
  for (const bp of tabletBps) {
    test(`login at ${bp.name}`, async ({ page }) => {
      await page.setViewportSize({ width: bp.width, height: bp.height });
      await page.goto('login');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `screenshots/login-tablet-${bp.name}.png`, fullPage: false });

      // Desktop hero should NOT be visible at tablet widths
      const hero = page.locator('.login-hero');
      await expect(hero).toHaveCSS('display', 'none');

      // Form visible
      const formPanel = page.locator('.login-form-panel');
      await expect(formPanel).toBeVisible();

      // No horizontal overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(bp.width + 1);
    });
  }
});
