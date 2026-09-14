/**
 * Daily Work Report — Main JS
 */

(function() {
  'use strict';

  // --- Theme Toggle (mobile header + sidebar) ---
  function toggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
  }

  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
  }

  var sidebarThemeToggle = document.getElementById('sidebarThemeToggle');
  if (sidebarThemeToggle) {
    sidebarThemeToggle.addEventListener('click', toggleTheme);
  }

  // --- System theme listener ---
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem('theme')) {
        document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
      }
    });
  }

})();
