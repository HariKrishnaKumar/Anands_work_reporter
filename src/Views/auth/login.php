<?php
$pageTitle = 'Login - Daily Work Report';
$showHeader = false;
require __DIR__ . '/../layouts/main.php';
?>

<div class="login-screen">
    <div class="login-card">
        <div class="login-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
            <h1 class="login-title">Daily Work Report</h1>
        </div>
        <p class="login-subtitle">Record your work. Keep your contribution visible.</p>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="login-error"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="<?= url('login') ?>">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input class="form-input" type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
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
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
