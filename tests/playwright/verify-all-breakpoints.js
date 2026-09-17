const { chromium } = require('playwright');

const viewports = [
  { name: 'mobile-320', width: 320, height: 568 },
  { name: 'mobile-390', width: 390, height: 844 },
  { name: 'mobile-414', width: 414, height: 896 },
  { name: 'tablet-768', width: 768, height: 1024 },
  { name: 'desktop-1366', width: 1366, height: 768 },
  { name: 'desktop-1920', width: 1920, height: 1080 },
];

(async () => {
  const browser = await chromium.launch();

  for (const vp of viewports) {
    const page = await browser.newPage({ viewport: { width: vp.width, height: vp.height } });
    await page.goto('http://localhost/daily-work-report/public/', { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);

    const checks = await page.evaluate(() => {
      const bg = document.getElementById('loginBg');
      const bgStyle = bg ? window.getComputedStyle(bg) : null;
      const hasImage = bgStyle && bgStyle.backgroundImage !== 'none' && bgStyle.backgroundImage !== '';

      // Count elements with background-image that reference unsplash
      const allEls = document.querySelectorAll('*');
      let unsplashBgCount = 0;
      for (let i = 0; i < allEls.length; i++) {
        const s = window.getComputedStyle(allEls[i]);
        if (s.backgroundImage && s.backgroundImage.includes('unsplash')) unsplashBgCount++;
      }

      // Check for old split elements
      const hasOldSplit = !!(
        document.querySelector('.login-hero') ||
        document.getElementById('loginRightBg') ||
        document.querySelector('.login-shell') ||
        document.getElementById('loginHeroImg')
      );

      // Check for horizontal overflow
      const hasOverflow = document.documentElement.scrollWidth > document.documentElement.clientWidth;

      return { hasImage, unsplashBgCount, hasOldSplit, hasOverflow };
    });

    const status = checks.hasImage && !checks.hasOldSplit && !checks.hasOverflow && checks.unsplashBgCount === 1 ? 'PASS' : 'FAIL';
    console.log(`${vp.name} (${vp.width}x${vp.height}): ${status} | bgImage=${checks.hasImage} unsplashCount=${checks.unsplashBgCount} oldSplit=${checks.hasOldSplit} overflow=${checks.hasOverflow}`);

    await page.screenshot({ path: `C:\\Users\\B_Hari_Krishna_Kumar\\Documents\\Anand\\employee task\\daily-work-report\\tests\\playwright\\screenshots\\login-one-bg-${vp.name}.png`, fullPage: false });
    await page.close();
  }

  await browser.close();
  console.log('\nAll screenshots saved.');
})();
