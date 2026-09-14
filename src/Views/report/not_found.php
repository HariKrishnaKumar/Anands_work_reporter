<?php
$pageTitle = 'Report Not Found';
$activeNav = '';
require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <div class="empty-state fade-in">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <h3>Report Not Found</h3>
        <p>The work report you are looking for does not exist or you do not have permission to view it.</p>
        <a href="<?= url('home') ?>" class="btn btn-primary" aria-label="Go to home page">Go to Home</a>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
