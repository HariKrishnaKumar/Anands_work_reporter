<?php
$pageTitle = 'Report Saved - Daily Work Report';
$activeNav = '';

// Load the saved report from session if available
$report = null;
$lastReportId = $_SESSION['last_report_id'] ?? null;
if ($lastReportId) {
    $reportRepo = new \App\Repositories\WorkReportRepository();
    $report = $reportRepo->findByIdAndUser((int)$lastReportId, currentUserId());
    if ($report) {
        $report['files'] = $reportRepo->getFilesByReport((int)$lastReportId, currentUserId());
    }
}

require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <div class="success-screen fade-in">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h1 class="success-title">Work Report Saved</h1>
        <p class="success-text">Your work has been recorded successfully.</p>

        <?php if (!empty($report)): ?>
        <div class="success-summary">
            <div class="review-section">
                <div class="review-label">Work Date</div>
                <div class="review-value"><?= e(formatDate($report['work_date'] ?? '')) ?></div>
            </div>
            <div class="review-section">
                <div class="review-label">Description</div>
                <div class="review-value"><?= e(mb_substr($report['description'] ?? '', 0, 120)) ?><?= mb_strlen($report['description'] ?? '') > 120 ? '...' : '' ?></div>
            </div>
            <?php if (!empty($report['files'])): ?>
            <div class="review-section">
                <div class="review-label">Attachments</div>
                <div class="review-value"><?= count($report['files']) ?> file<?= count($report['files']) > 1 ? 's' : '' ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="success-actions">
            <a href="<?= url('home') ?>" class="btn btn-primary btn-full btn-lg">Go to Home</a>
            <a href="<?= url('report/add') ?>" class="btn btn-secondary btn-full btn-lg">Add Another Report</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>