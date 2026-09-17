const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

  await page.goto('https://www.yajurvedh.com/', { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForTimeout(8000);

  // Screenshots
  await page.screenshot({ path: '../test-results/yajurvedh-homepage.png', fullPage: false });
  await page.screenshot({ path: '../test-results/yajurvedh-fullpage.png', fullPage: true });

  // Extract logo info
  const logos = await page.evaluate(() => {
    const results = [];
    const imgs = document.querySelectorAll('img');
    imgs.forEach(img => {
      const src = img.src || '';
      const alt = img.alt || '';
      if (src.toLowerCase().includes('logo') || alt.toLowerCase().includes('logo') ||
          img.closest('.logo, .brand, .navbar-brand, header a, nav a, [class*="logo"]')) {
        results.push({
          src: src,
          alt: alt,
          width: img.naturalWidth,
          height: img.naturalHeight,
          className: img.className,
          parentTag: img.parentElement ? img.parentElement.tagName : '',
          parentClass: img.parentElement ? img.parentElement.className : ''
        });
      }
    });

    // SVG logos
    const svgs = document.querySelectorAll('svg');
    svgs.forEach(svg => {
      const closestLink = svg.closest('a');
      if (closestLink || (svg.closest('[class*="logo"], [class*="brand"]'))) {
        results.push({
          type: 'svg',
          className: svg.getAttribute('class') || '',
          parentClass: closestLink ? closestLink.className : svg.parentElement.className,
          parentHref: closestLink ? closestLink.href : '',
          svgSnippet: svg.outerHTML.substring(0, 800)
        });
      }
    });

    return results;
  });
  console.log('=== LOGOS ===');
  console.log(JSON.stringify(logos, null, 2));

  // Extract fonts
  const fonts = await page.evaluate(() => {
    const fontSet = new Set();
    const els = document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, a, button, span, li, td, th');
    els.forEach(el => {
      fontSet.add(window.getComputedStyle(el).fontFamily);
    });

    const fontFaces = [];
    for (const sheet of document.styleSheets) {
      try {
        for (const rule of sheet.cssRules) {
          if (rule.type === CSSRule.FONT_FACE_RULE) {
            fontFaces.push({
              family: rule.style.getPropertyValue('font-family'),
              src: rule.style.getPropertyValue('src'),
              weight: rule.style.getPropertyValue('font-weight'),
              style: rule.style.getPropertyValue('font-style')
            });
          }
        }
      } catch (e) { /* cross-origin */ }
    }

    const fontLinks = [];
    document.querySelectorAll('link').forEach(link => {
      const href = link.href || '';
      if (href.includes('fonts') || href.includes('typekit') || href.includes('font')) {
        fontLinks.push(href);
      }
    });

    return {
      computedFamilies: [...fontSet],
      fontFaces: fontFaces,
      fontLinks: fontLinks
    };
  });
  console.log('\n=== FONTS ===');
  console.log(JSON.stringify(fonts, null, 2));

  // Page meta
  const meta = await page.evaluate(() => ({
    title: document.title,
    description: document.querySelector('meta[name="description"]')?.content || ''
  }));
  console.log('\n=== META ===');
  console.log(JSON.stringify(meta, null, 2));

  // Header/nav HTML
  const headerHTML = await page.evaluate(() => {
    const header = document.querySelector('header, nav, .navbar, .header, [class*="nav"]');
    return header ? header.outerHTML.substring(0, 3000) : 'No header found';
  });
  console.log('\n=== HEADER HTML (first 3000 chars) ===');
  console.log(headerHTML);

  // Body font info
  const bodyFont = await page.evaluate(() => {
    const body = document.body;
    const cs = window.getComputedStyle(body);
    return {
      fontFamily: cs.fontFamily,
      fontSize: cs.fontSize,
      fontWeight: cs.fontWeight,
      lineHeight: cs.lineHeight,
      color: cs.color,
      backgroundColor: cs.backgroundColor
    };
  });
  console.log('\n=== BODY FONT ===');
  console.log(JSON.stringify(bodyFont, null, 2));

  await browser.close();
})();
