<?php
$user = currentUser();
$reports = $reports ?? [];
$pageTitle = 'Home - Daily Work Report';
$activeNav = 'home';
$searchQuery = $searchQuery ?? '';
$searchDate = $searchDate ?? '';
require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <section class="greeting-section fade-in">
        <p class="greeting-text"><?= e(getGreeting()) ?>,</p>
        <h1 class="greeting-name"><?= e($user['display_name'] ?? 'User') ?></h1>
        <p class="greeting-date"><?= e(fullDate()) ?></p>
    </section>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <a href="<?= url('report/add') ?>" class="quick-action fade-in" aria-label="Add work report">
        <div class="action-card">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Add Work Report</span>
        </div>
    </a>

    <?php if (!empty($reports)): ?>
    <!-- Search Bar -->
    <div class="search-bar fade-in">
        <div class="search-row">
            <div class="search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Search reports..." value="<?= e($searchQuery) ?>" aria-label="Search work reports by description">
            </div>
            <input type="date" id="searchDate" class="search-date" value="<?= e($searchDate) ?>" aria-label="Filter by date">
            <button type="button" id="searchClear" class="search-clear" style="display:none;" aria-label="Clear search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Clear
            </button>
        </div>
        <div id="searchResultsInfo" class="search-results-info" style="display:none;"></div>
    </div>

    <div class="section-header fade-in">
        <h2 class="section-header-title">My Work Reports</h2>
        <span class="section-header-count"><?= count($reports) ?></span>
    </div>

    <div class="report-list fade-in">
        <?php foreach ($reports as $report): ?>
        <a href="<?= url('report/' . $report['id']) ?>" class="report-card stagger-item" aria-label="View report for <?= e(formatDate($report['work_date'])) ?>" data-description="<?= e($report['description']) ?>" data-date="<?= e($report['work_date']) ?>">
            <div class="report-card-content">
                <div class="report-card-date"><?= e(formatDate($report['work_date'])) ?></div>
                <div class="report-card-desc"><?= e(mb_substr($report['description'], 0, 80)) ?><?= mb_strlen($report['description']) > 80 ? '...' : '' ?></div>
                <?php if (!empty($report['files'])): ?>
                <div class="report-card-meta">
                    <span class="report-card-files">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <?= count($report['files']) ?> file<?= count($report['files']) > 1 ? 's' : '' ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="report-card-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($reports)): ?>
    <div class="empty-state fade-in">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
        <h3>No work reports yet</h3>
        <p>Start tracking your daily work by adding your first report.</p>
        <a href="<?= url('report/add') ?>" class="btn btn-primary" aria-label="Add your first report">Add Work Report</a>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
