<?php
$pageTitle = 'Login - Yajurvedh Work Report';
$showHeader = false;
require __DIR__ . '/../layouts/main.php';
?>

<div class="login-page">
    <!-- ONE single full-screen background image -->
    <div id="loginBg" class="login-bg"></div>
    <div class="login-bg-overlay"></div>

    <!-- LEFT: Brand content (positioned over the single background) -->
    <div class="login-brand">
        <div class="login-brand-logo">
            <img src="<?= url('assets/images/logo.png') ?>" alt="Yajurvedh logo" width="54" height="54" />
            <span>Yajurvedh</span>
        </div>
        <div id="loginQuote" class="login-brand-quote">Small steps. Meaningful progress.</div>
    </div>

    <!-- RIGHT: Matte frosted glass login panel -->
    <div class="login-panel">
        <div class="login-form-glass">
            <div class="login-form-content">
                <div class="login-form-logo">
                    <img src="<?= url('assets/images/logo.png') ?>" alt="Yajurvedh logo" width="28" height="28" />
                    <h1 class="login-title">Yajurvedh Work Report</h1>
                </div>
                <p class="login-subtitle">Sign in with your Zoho account to continue.</p>

                <!-- Error toast (replaces inline flash + inline error div) -->
                <div id="loginToast" class="login-toast" style="display:none;" role="alert">
                    <svg class="login-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span id="loginToastMsg"></span>
                </div>

                <form class="login-form" id="loginForm" method="POST" action="<?= url('login') ?>">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-input" type="email" id="email" name="email" placeholder="you@company.com" required autocomplete="email">
                    </div>

                    <div class="form-group password-group">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-input password-input" type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" id="passwordToggle" aria-label="Show password">
                            <svg class="icon-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">Login</button>
                </form>

                <?php if (isDevEnvironment()): ?>
                <div class="login-divider">OR</div>
                <a href="<?= url('dev-login') ?>" class="dev-login-btn" aria-label="Dev Login (One-Click)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10"/></svg>
                    Dev Login (One-Click)
                </a>
                <?php endif; ?>

                <p class="login-footer-text">Secure &middot; Internal Use Only</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
  // --- Password toggle ---
  var toggleBtn = document.getElementById('passwordToggle');
  var passInput = document.getElementById('password');
  var iconOpen = toggleBtn ? toggleBtn.querySelector('.icon-eye-open') : null;
  var iconClosed = toggleBtn ? toggleBtn.querySelector('.icon-eye-closed') : null;

  if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', function() {
      var isPassword = passInput.type === 'password';
      passInput.type = isPassword ? 'text' : 'password';
      if (iconOpen) iconOpen.style.display = isPassword ? 'none' : '';
      if (iconClosed) iconClosed.style.display = isPassword ? '' : 'none';
      toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  }

  // --- Error toast helper ---
  function showToast(msg, duration) {
    var toast = document.getElementById('loginToast');
    var toastMsg = document.getElementById('loginToastMsg');
    if (!toast || !toastMsg) return;
    toastMsg.textContent = msg;
    toast.style.display = 'flex';
    toast.classList.remove('toast-hide');
    toast.classList.add('toast-show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(function() {
      toast.classList.remove('toast-show');
      toast.classList.add('toast-hide');
      setTimeout(function() { toast.style.display = 'none'; }, 400);
    }, duration || 4000);
  }

  // --- Show server-side flash error as toast ---
  <?php $flash = getFlash(); if ($flash): ?>
  showToast(<?= json_encode($flash['message']) ?>);
  <?php endif; ?>

  // --- Client-side validation ---
  var form = document.getElementById('loginForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      var email = document.getElementById('email').value.trim();
      var pass = document.getElementById('password').value;
      if (!email || !pass) {
        e.preventDefault();
        showToast('Email and password are required.');
        return;
      }
    });
  }

  // Expose showToast globally for any future use
  window.showToast = showToast;
})();
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>