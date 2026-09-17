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
            <div class="login-form-top">
                <button class="theme-switcher login-theme-switcher" role="switch" aria-checked="false" aria-label="Toggle theme" tabindex="0">
                    <div class="theme-switcher-icons">
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </div>
                    <div class="theme-switcher-thumb"></div>
                </button>
            </div>

            <div class="login-form-content">
                <div class="login-form-logo">
                    <img src="<?= url('assets/images/logo.png') ?>" alt="Yajurvedh logo" width="28" height="28" />
                    <h1 class="login-title">Yajurvedh Work Report</h1>
                </div>
                <p class="login-subtitle">Sign in with your Zoho account to continue.</p>

                <?php $flash = getFlash(); if ($flash): ?>
                    <div class="login-error"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <form class="login-form" id="loginForm" method="POST" action="<?= url('login') ?>">
                    <?= csrfField() ?>
                    <div id="loginError" style="display:none;color:var(--error,#ef4444);font-size:0.85rem;margin-bottom:8px;text-align:center;" role="alert"></div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-input" type="email" id="email" name="email" placeholder="you@company.com" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-input" type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg">Login ?</button>
                </form>
                <script>
                document.getElementById("loginForm").addEventListener("submit", function(e) {
                    var email = document.getElementById("email").value.trim();
                    var pass = document.getElementById("password").value;
                    var err = document.getElementById("loginError");
                    if (!email || !pass) {
                        e.preventDefault();
                        err.textContent = "Email and password are required.";
                        err.style.display = "block";
                    }
                });
                </script>

                <?php if (isDevEnvironment()): ?>
                <div class="login-divider">OR</div>
                <a href="<?= url('dev-login') ?>" class="dev-login-btn" aria-label="Dev Login (One-Click)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10"/></svg>
                    Dev Login (One-Click)
                </a>
                <?php endif; ?>

                <p class="login-footer-text">Secure · Internal Use Only</p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>