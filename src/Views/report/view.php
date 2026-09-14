<?php
$pageTitle = 'Report Details - Daily Work Report';
$activeNav = '';
require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <a href="<?= url('home') ?>" class="back-link" aria-label="Go back to home">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Home
    </a>

    <div class="detail-section">
        <div style="display:flex; align-items:center; justify-content:space-between; gap: 12px; flex-wrap: wrap;">
            <h1 class="heading-lg">Work Report</h1>
            <span class="readonly-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Read Only
            </span>
        </div>
    </div>

    <div class="detail-section fade-in">
        <div class="detail-label">Work Date</div>
        <div class="detail-value"><?= e(formatDate($report['work_date'])) ?></div>
    </div>

    <div class="detail-section fade-in">
        <div class="detail-label">Description</div>
        <div class="detail-description"><?= nl2br(e($report['description'])) ?></div>
    </div>

    <?php if (!empty($report['files'])): ?>
    <div class="detail-section fade-in">
        <div class="detail-label">Attached Files</div>
        <div class="file-list">
            <?php foreach ($report['files'] as $file): ?>
            <div class="file-card">
                <div class="file-card-icon">
                    <?= getFileIcon($file['mime_type']) ?>
                </div>
                <div class="file-card-info">
                    <div class="file-item-name"><?= e($file['original_filename']) ?></div>
                    <div class="file-item-meta"><?= e($file['mime_type']) ?> &middot; <?= formatFileSize((int)$file['file_size']) ?></div>
                </div>
                <div class="file-card-actions">
                    <?php if (str_starts_with($file['mime_type'], 'image/')): ?>
                    <a href="<?= url('files/' . $file['id']) ?>" target="_blank" class="btn btn-sm btn-secondary" aria-label="Preview <?= e($file['original_filename']) ?>">Preview</a>
                    <?php else: ?>
                    <a href="<?= url('files/' . $file['id']) ?>" class="btn btn-sm btn-secondary" aria-label="Download <?= e($file['original_filename']) ?>">Download</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="detail-section fade-in" style="text-align:center;">
        <p class="text-muted">This submitted report cannot be edited.</p>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
