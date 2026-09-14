// @ts-check
// Shared test helpers for viewport-aware navigation

/**
 * Click the "Add Report" button/link. Works on both mobile (bottom nav)
 * and tablet+ (sidebar).
 */
async function clickAddReport(page) {
  const viewportWidth = page.viewportSize()?.width || 390;
  if (viewportWidth >= 768) {
    // Tablet/Desktop: use sidebar nav
    await page.click('.sidebar-nav-item:nth-child(2)');
  } else {
    // Mobile: use bottom nav
    await page.click('.nav-item-add');
  }
}

module.exports = { clickAddReport };
