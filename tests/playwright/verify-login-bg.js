const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });
  
  await page.goto('http://localhost/daily-work-report/public/', { waitUntil: 'networkidle', timeout: 30000 });
  
  // Wait for image to load
  await page.waitForTimeout(3000);
  
  // Check 1: ONE single background image layer
  const bgElements = await page.evaluate(() => {
    const bg = document.getElementById('loginBg');
    const bgStyle = bg ? window.getComputedStyle(bg) : null;
    const bgImage = bgStyle ? bgStyle.backgroundImage : 'none';
    
    return {
      bgExists: !!bg,
      bgPosition: bgStyle ? bgStyle.position : 'none',
      bgWidth: bgStyle ? bgStyle.width : 'none',
      bgHeight: bgStyle ? bgStyle.height : 'none',
      bgImage: bgImage,
      bgZIndex: bgStyle ? bgStyle.zIndex : 'none',
    };
  });
  console.log('Background layer:', JSON.stringify(bgElements, null, 2));
  
  // Check 2: No login-hero, login-right-bg, login-shell, or left-image elements
  const oldElements = await page.evaluate(() => {
    return {
      loginHero: !!document.querySelector('.login-hero'),
      loginRightBg: !!document.getElementById('loginRightBg'),
      loginShell: !!document.querySelector('.login-shell'),
      loginHeroImg: !!document.getElementById('loginHeroImg'),
      loginHeroCredit: !!document.getElementById('loginCredit'),
    };
  });
  console.log('Old elements (should all be false):', JSON.stringify(oldElements, null, 2));
  
  // Check 3: Brand + panel exist
  const uiElements = await page.evaluate(() => {
    return {
      brand: !!document.querySelector('.login-brand'),
      brandLogo: !!document.querySelector('.login-brand-logo'),
      brandQuote: !!document.querySelector('.login-brand-quote'),
      panel: !!document.querySelector('.login-panel'),
      glass: !!document.querySelector('.login-form-glass'),
      form: !!document.querySelector('.login-form'),
      email: !!document.getElementById('email'),
      password: !!document.getElementById('password'),
    };
  });
  console.log('UI elements:', JSON.stringify(uiElements, null, 2));
  
  // Check 4: No center seam (no borders, no dividers between regions)
  const seamCheck = await page.evaluate(() => {
    const allElements = document.querySelectorAll('*');
    const borders = [];
    for (let i = 0; i < allElements.length; i++) {
      const el = allElements[i];
      const style = window.getComputedStyle(el);
      const borderLeft = style.borderLeftWidth;
      const borderRight = style.borderRightWidth;
      if (borderLeft && borderLeft !== '0px' && borderLeft !== 'medium') {
        // Skip non-visible borders
        if (parseFloat(borderLeft) > 0) {
          borders.push({ tag: el.tagName, class: el.className, side: 'left', width: borderLeft });
        }
      }
      if (borderRight && borderRight !== '0px' && borderRight !== 'medium') {
        if (parseFloat(borderRight) > 0) {
          borders.push({ tag: el.tagName, class: el.className, side: 'right', width: borderRight });
        }
      }
    }
    return borders;
  });
  console.log('Visible borders (potential seams):', JSON.stringify(seamCheck, null, 2));
  
  // Check 5: Background image actually loaded
  const bgLoaded = await page.evaluate(() => {
    const bg = document.getElementById('loginBg');
    if (!bg) return false;
    const style = window.getComputedStyle(bg);
    return style.backgroundImage !== 'none' && style.backgroundImage !== '';
  });
  console.log('Background image loaded:', bgLoaded);
  
  // Screenshot
  await page.screenshot({ path: 'C:\\Users\\B_Hari_Krishna_Kumar\\Documents\\Anand\\employee task\\daily-work-report\\tests\\playwright\\screenshots\\login-one-bg-1366.png', fullPage: false });
  console.log('Screenshot saved.');
  
  await browser.close();
})();
