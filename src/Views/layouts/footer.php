        <?php if (isLoggedIn()): ?>
        <nav class="bottom-nav" aria-label="Main navigation">
            <div class="nav-pill-bg"></div>
            <a href="<?= url('home') ?>" class="nav-item <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>" aria-current="<?= ($activeNav ?? '') === 'home' ? 'page' : 'false' ?>" aria-label="Home">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Home</span>
            </a>
            <a href="<?= url('report/add') ?>" class="nav-item <?= ($activeNav ?? '') === 'add' ? 'active' : '' ?>" aria-current="<?= ($activeNav ?? '') === 'add' ? 'page' : 'false' ?>" aria-label="Add Work Report">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Add</span>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
        <?php endif; ?>

        <script src="<?= url('assets/js/app.js') ?>"></script>
        </div><!-- .main-area -->
    </div><!-- .app-container -->
</body>
</html>
