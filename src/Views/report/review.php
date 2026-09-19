<?php
$pageTitle = 'Review Work Report';
$activeNav = '';
$draft = $draft ?? $_SESSION['report_draft'] ?? [];
$files = $_SESSION['report_files'] ?? [];
require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <a href="<?= url('report/add') ?>" class="back-link" aria-label="Go back to add report">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back
    </a>

    <h1 class="heading-lg">Review Work Report</h1>

    <div class="review-section fade-in">
        <div class="review-label">Work Date</div>
        <div class="review-value"><?= e(formatDate($draft['work_date'] ?? '')) ?></div>
    </div>

    <div class="review-section fade-in">
        <div class="review-label">Description</div>
        <div class="review-value"><?= e($draft['description'] ?? '') ?></div>
    </div>

    <?php if (!empty($files)): ?>
    <div class="review-section fade-in">
        <div class="review-label">Attached Files</div>
        <div class="review-files">
            <?php foreach ($files as $file): ?>
            <div class="review-file">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span><?= e($file['name'] ?? 'Unknown file') ?></span>
                <span style="color:var(--text-muted); margin-left:auto;"><?= formatFileSize((int)($file['size'] ?? 0)) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="btn-row">
        <a href="<?= url('report/add') ?>" class="btn btn-secondary btn-lg">Back</a>
        <button type="button" id="saveBtn" class="btn btn-primary btn-lg" style="flex:2;" aria-label="Save work report">Save Work Report</button>
    </div>

    <div class="modal-overlay" id="confirmModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <div class="modal-title" id="modalTitle">Save Work Report?</div>
            <div class="modal-text">Once saved, this report becomes read-only and cannot be edited or deleted.</div>
            <div class="modal-actions">
                <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                <form method="POST" action="<?= url('report/save') ?>" id="saveForm" style="flex:2;">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Save Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
(function(){
  const saveBtn = document.getElementById('saveBtn');
  const modal = document.getElementById('confirmModal');
  const cancelBtn = document.getElementById('cancelBtn');
  const saveForm = document.getElementById('saveForm');
  const submitBtn = saveForm.querySelector('button[type="submit"]');

  saveBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
  });

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });

  saveForm.addEventListener('submit', () => {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
  });
})();
</script>
JS;
require __DIR__ . '/../layouts/footer.php';
?>
