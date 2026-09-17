/**
 * Daily Work Report — Main JS
 * Apple-Inspired Premium Productivity UI
 */

(function() {
  'use strict';

  // ========================================
  // Theme Switcher — Premium Pill
  // ========================================

  function getSystemTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
      return 'light';
    }
    return 'dark';
  }

  function getTheme() {
    return localStorage.getItem('theme') || getSystemTheme();
  }

  function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
  }

  function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || getTheme();
    var next = current === 'dark' ? 'light' : 'dark';
    setTheme(next);
  }

  // Initialize theme
  setTheme(getTheme());

  // Theme switcher buttons (all .theme-switcher elements)
  var switchers = document.querySelectorAll('.theme-switcher');
  switchers.forEach(function(switcher) {
    switcher.addEventListener('click', toggleTheme);
    switcher.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleTheme();
      }
    });
  });

  // System theme listener
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
      if (!localStorage.getItem('theme')) {
        setTheme(e.matches ? 'dark' : 'light');
      }
    });
  }

  // ========================================
  // Navigation Active Pill — Sliding
  // ========================================

  function initNavPill() {
    var nav = document.querySelector('.bottom-nav');
    if (!nav) return;

    var pillBg = nav.querySelector('.nav-pill-bg');
    var items = nav.querySelectorAll('.nav-item');
    if (!pillBg || items.length === 0) return;

    function positionPill() {
      var activeItem = nav.querySelector('.nav-item.active');
      if (!activeItem) {
        pillBg.style.opacity = '0';
        return;
      }

      var navRect = nav.getBoundingClientRect();
      var itemRect = activeItem.getBoundingClientRect();
      // Account for nav padding (6px) + any gap
      var navPadding = parseFloat(getComputedStyle(nav).paddingLeft) || 6;
      var offsetLeft = itemRect.left - navRect.left - navPadding;
      var itemWidth = itemRect.width;

      pillBg.style.width = itemWidth + 'px';
      pillBg.style.transform = 'translateX(' + offsetLeft + 'px)';
      pillBg.style.opacity = '1';
    }

    // Position on load with small delay to ensure layout is settled
    requestAnimationFrame(function() {
      requestAnimationFrame(positionPill);
    });

    // Reposition on resize
    var resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(positionPill, 50);
    });

    // Reposition after fonts load
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(positionPill);
    }

    // Also reposition on orientation change
    window.addEventListener('orientationchange', function() {
      setTimeout(positionPill, 100);
    });
  }

  // Initialize nav pill
  initNavPill();

  // ========================================
  // Search — Client-side filtering
  // ========================================

  function initSearch() {
    var searchInput = document.getElementById('searchInput');
    var searchDate = document.getElementById('searchDate');
    var searchClear = document.getElementById('searchClear');
    var searchInfo = document.getElementById('searchResultsInfo');
    var reportList = document.querySelector('.report-list');
    var emptyState = document.querySelector('.empty-state');

    if (!searchInput || !reportList) return;

    var cards = reportList.querySelectorAll('.report-card');
    var allCards = Array.from(cards);

    function filterReports() {
      var query = (searchInput.value || '').toLowerCase().trim();
      var dateVal = searchDate ? searchDate.value : '';
      var visibleCount = 0;

      allCards.forEach(function(card) {
        var desc = (card.getAttribute('data-description') || '').toLowerCase();
        var date = card.getAttribute('data-date') || '';

        var matchesQuery = !query || desc.indexOf(query) !== -1;
        var matchesDate = !dateVal || date === dateVal;

        if (matchesQuery && matchesDate) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Update results info
      if (searchInfo) {
        if (query || dateVal) {
          searchInfo.textContent = visibleCount + ' result' + (visibleCount !== 1 ? 's' : '') + ' found';
          searchInfo.style.display = '';
        } else {
          searchInfo.style.display = 'none';
        }
      }

      // Show/hide clear button
      if (searchClear) {
        searchClear.style.display = (query || dateVal) ? '' : 'none';
      }

      // Show empty state if no results
      if (emptyState) {
        if (visibleCount === 0 && (query || dateVal)) {
          emptyState.style.display = '';
          emptyState.querySelector('h3').textContent = 'No matching reports';
          emptyState.querySelector('p').textContent = 'Try adjusting your search terms.';
        } else if (visibleCount === 0 && !query && !dateVal) {
          emptyState.style.display = '';
          emptyState.querySelector('h3').textContent = 'No work reports yet';
          emptyState.querySelector('p').textContent = 'Start tracking your daily work by adding your first report.';
        } else {
          emptyState.style.display = 'none';
        }
      }
    }

    searchInput.addEventListener('input', filterReports);
    if (searchDate) {
      searchDate.addEventListener('change', filterReports);
    }

    if (searchClear) {
      searchClear.addEventListener('click', function() {
        searchInput.value = '';
        if (searchDate) searchDate.value = '';
        filterReports();
        searchInput.focus();
      });
    }

    // Initial filter
    filterReports();
  }

  initSearch();

  // ========================================
  // Login Background — ONE Full-Screen Unsplash Image
  // API: max 1 request per hour (cached in localStorage)
  // Rotation: crossfade through cached pool every 5s
  // Text color: auto-adapts to image brightness
  // ========================================

  function initLoginHero() {
    var loginBg = document.getElementById('loginBg');
    var quoteEl = document.getElementById('loginQuote');
    var brandLogoText = document.querySelector('.login-brand-logo span');
    if (!loginBg) return;

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var CACHE_KEY = 'unsplash_image_cache';
    var CACHE_TTL = 3600000; // 1 hour in ms

    var quotes = [
      'Small steps. Meaningful progress.',
      'Consistency creates results.',
      'Work with purpose.',
      'Progress begins with action.',
      'Keep moving forward.',
      'Good work compounds.',
      'Make today count.',
      'Progress takes patience.',
      'Focus. Build. Improve.',
      'Do the work. Grow.',
      'Consistency beats intensity.'
    ];

    var queries = [
      'nature landscape',
      'peaceful mountains',
      'calm lake',
      'forest landscape',
      'sunrise mountains',
      'calm ocean',
      'misty forest',
      'waterfall nature'
    ];

    var imagePool = [];
    var currentIndex = -1;
    var lastQuoteIndex = -1;
    var rotationTimer = null;

    // --- Luminance detection from Unsplash color field ---
    function hexToRgb(hex) {
      hex = hex.replace('#', '');
      if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
      return {
        r: parseInt(hex.substring(0, 2), 16),
        g: parseInt(hex.substring(2, 4), 16),
        b: parseInt(hex.substring(4, 6), 16)
      };
    }

    function getLuminance(hexColor) {
      var rgb = hexToRgb(hexColor || '#333333');
      return 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b;
    }

    function applyTextColor(photo) {
      var lum = getLuminance(photo.color || '#333333');
      var isDark = lum < 140;
      var textColor = isDark ? '#b1965f' : '#1a1a2e';
      var shadowColor = isDark ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.3)';
      // Update brand logo text color
      if (brandLogoText) {
        brandLogoText.style.color = textColor;
        brandLogoText.style.textShadow = '0 2px 10px ' + shadowColor;
      }
      // Update quote color
      if (quoteEl) {
        quoteEl.style.color = isDark ? 'rgba(255,255,255,0.85)' : 'rgba(0,0,0,0.7)';
        quoteEl.style.textShadow = '0 1px 6px ' + shadowColor;
      }
    }

    // --- Cache management ---
    function getCachedImages() {
      try {
        var raw = localStorage.getItem(CACHE_KEY);
        if (!raw) return null;
        var cache = JSON.parse(raw);
        if (!cache || !cache.images || !cache.timestamp) return null;
        if (Date.now() - cache.timestamp > CACHE_TTL) return null;
        if (!Array.isArray(cache.images) || cache.images.length === 0) return null;
        return cache.images;
      } catch (e) {
        return null;
      }
    }

    function setCachedImages(images) {
      try {
        localStorage.setItem(CACHE_KEY, JSON.stringify({
          images: images,
          timestamp: Date.now()
        }));
      } catch (e) {}
    }

    // --- Quote helpers ---
    function getRandomQuote() {
      var idx;
      do {
        idx = Math.floor(Math.random() * quotes.length);
      } while (idx === lastQuoteIndex && quotes.length > 1);
      lastQuoteIndex = idx;
      return quotes[idx];
    }

    function getRandomQuery() {
      return queries[Math.floor(Math.random() * queries.length)];
    }

    // --- Display image on the ONE full-screen background ---
    function displayImage(index) {
      if (index < 0 || index >= imagePool.length) return;
      var photo = imagePool[index];
      currentIndex = index;

      var preloader = new Image();
      preloader.onload = function() {
        applyTextColor(photo);

        if (reducedMotion) {
          loginBg.style.backgroundImage = 'url(' + photo.url + ')';
        } else {
          // Crossfade: fade out, swap, fade in
          loginBg.style.opacity = '0';
          setTimeout(function() {
            loginBg.style.backgroundImage = 'url(' + photo.url + ')';
            loginBg.style.opacity = '1';
          }, 350);
        }

        // Update quote
        if (quoteEl) {
          if (reducedMotion) {
            quoteEl.textContent = getRandomQuote();
          } else {
            quoteEl.style.opacity = '0';
            setTimeout(function() {
              quoteEl.textContent = getRandomQuote();
              quoteEl.style.opacity = '1';
            }, 250);
          }
        }
      };
      preloader.onerror = function() {
        if (imagePool.length > 1) {
          var nextIdx = (index + 1) % imagePool.length;
          if (nextIdx !== index) displayImage(nextIdx);
        }
      };
      preloader.src = photo.url;
    }

    // --- Rotate to next cached image (no API call) ---
    function nextImage() {
      if (imagePool.length === 0) return;
      var nextIndex = currentIndex + 1;
      if (nextIndex >= imagePool.length) nextIndex = 0;
      displayImage(nextIndex);
    }

    // --- Fetch from API (only when cache is expired/missing) ---
    function fetchImages(callback) {
      console.log('[Unsplash] API CALL — fetching new batch (max once per hour)');
      var query = getRandomQuery();
      var xhr = new XMLHttpRequest();
      xhr.open('GET', 'api/unsplash.php?count=8&query=' + encodeURIComponent(query) + '&orientation=landscape&content_filter=high', true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.images && data.images.length > 0) {
              setCachedImages(data.images);
              console.log('[Unsplash] API OK — cached ' + data.images.length + ' images');
              if (callback) callback(data.images);
              return;
            }
          } catch (e) {
            console.warn('[Unsplash] parse error', e);
          }
        } else {
          console.warn('[Unsplash] status', xhr.status);
        }
        var stale = getCachedImagesStale();
        if (stale && stale.length > 0 && callback) {
          console.log('[Unsplash] stale cache fallback');
          callback(stale);
        }
      };
      xhr.onerror = function() {
        console.warn('[Unsplash] network error');
        var stale = getCachedImagesStale();
        if (stale && stale.length > 0 && callback) callback(stale);
      };
      xhr.send();
    }

    function getCachedImagesStale() {
      try {
        var raw = localStorage.getItem(CACHE_KEY);
        if (!raw) return null;
        var cache = JSON.parse(raw);
        if (cache && Array.isArray(cache.images) && cache.images.length > 0) return cache.images;
      } catch (e) {}
      return null;
    }

    // --- Start ---
    var cached = getCachedImages();
    if (cached) {
      console.log('[Unsplash] CACHED — ' + cached.length + ' images, NO API call');
      imagePool = cached;
      currentIndex = -1;
      displayImage(0);
      startRotation();
    } else {
      console.log('[Unsplash] NO CACHE — fetching from API');
      fetchImages(function(images) {
        if (images && images.length > 0) {
          imagePool = images;
          currentIndex = -1;
          displayImage(0);
          startRotation();
        }
      });
    }

    function startRotation() {
      if (rotationTimer) clearInterval(rotationTimer);
      var interval = reducedMotion ? 10000 : 5000;
      rotationTimer = setInterval(nextImage, interval);
    }

    window.addEventListener('beforeunload', function() {
      if (rotationTimer) clearInterval(rotationTimer);
    });
  }

  initLoginHero();

  // ========================================
  // Page Entry Animation
  // ========================================

  function initPageAnimation() {
    var content = document.querySelector('.page-content');
    if (content) {
      content.style.opacity = '1';
    }
  }

  initPageAnimation();

})();
