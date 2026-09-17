<?php
$pageTitle = 'Add Work Report';
$activeNav = 'add';
$today = $today ?? (new DateTime())->format('Y-m-d');
require __DIR__ . '/../layouts/main.php';
?>

<div class="page-content">
    <a href="<?= url('home') ?>" class="back-link" aria-label="Go back to home">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back
    </a>

    <h1 class="heading-lg">Add Work Report</h1>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form id="addReportForm" method="POST" action="<?= url('report/preview') ?>" enctype="multipart/form-data">
        <?= csrfField() ?>

        <div class="form-group">
            <label class="form-label" for="work_date">Work Date</label>
            <input class="form-input" type="date" id="work_date" name="work_date" value="<?= e($today) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Work Description</label>
            <textarea class="form-input" id="description" name="description" placeholder="Describe what you worked on today..." required minlength="10" maxlength="1000"></textarea>
            <div class="form-error" id="descError" style="display:none;color:var(--error,#ef4444);font-size:0.85rem;margin-top:4px;" role="alert"></div>
            <div class="form-hint">
                <span class="char-counter" id="charCounter">0/1000</span>
                <span>Minimum 10 characters</span>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Attach Files (Optional)</label>
            <div class="upload-area" id="uploadArea" role="button" tabindex="0" aria-label="Click or drag files to upload">
                <div class="upload-area-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div class="upload-area-text">Tap to Upload</div>
                <div class="upload-area-hint">Images, PDF, DOC, DOCX (Max 10MB each)</div>
                <div class="upload-area-browse">Browse Files</div>
            </div>
            <input type="file" id="fileInput" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" style="display:none" aria-hidden="true">
        </div>

        <div class="file-list" id="fileList" aria-live="polite"></div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary btn-full btn-lg">Review</button>
        </div>
    </form>
</div>

<?php
$extraScripts = <<<'JS'
<script>
(function(){
  const uploadArea = document.getElementById('uploadArea');
  const fileInput = document.getElementById('fileInput');
  const fileList = document.getElementById('fileList');
  const charCounter = document.getElementById('charCounter');
  const description = document.getElementById('description');
  let selectedFiles = [];

  const descError = document.getElementById('descError');
  const form = document.getElementById('addReportForm');
  
  description.addEventListener('input', () => {
    const len = description.value.length;
    charCounter.textContent = len + '/1000';
    if (len > 0 && len < 10) {
      descError.textContent = 'Description must be at least 10 characters (' + len + '/10)';
      descError.style.display = 'block';
    } else {
      descError.style.display = 'none';
    }
  });

  form.addEventListener('submit', (e) => {
    if (description.value.trim().length < 10) {
      e.preventDefault();
      descError.textContent = 'Description must be at least 10 characters';
      descError.style.display = 'block';
      description.focus();
    }
  });

  uploadArea.addEventListener('click', () => fileInput.click());
  uploadArea.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
  });
  uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
  uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
  });

  fileInput.addEventListener('change', () => {
    handleFiles(fileInput.files);
  });

  function handleFiles(files) {
    const allowedExt = ['jpg','jpeg','png','gif','pdf','doc','docx'];
    for (let f of files) {
      const ext = f.name.split('.').pop().toLowerCase();
      if (!allowedExt.includes(ext)) {
        alert('File type not allowed: ' + f.name);
        continue;
      }
      if (f.size > 10 * 1024 * 1024) {
        alert('File too large: ' + f.name + ' (max 10MB)');
        continue;
      }
      if (selectedFiles.some(s => s.name === f.name && s.size === f.size)) continue;
      selectedFiles.push(f);
    }
    renderFiles();
  }

  function renderFiles() {
    fileList.innerHTML = '';
    if (selectedFiles.length === 0) return;
    selectedFiles.forEach((f, i) => {
      const item = document.createElement('div');
      item.className = 'file-item';
      const ext = f.name.split('.').pop().toLowerCase();
      const icon = ext === 'pdf' ? 'PDF' : (['doc','docx'].includes(ext) ? 'DOC' : 'IMG');
      const size = f.size >= 1048576 ? (f.size/1048576).toFixed(1)+' MB' : (f.size/1024).toFixed(1)+' KB';
      item.innerHTML = `
        <div class="file-item-icon"><span style="font-size:10px;font-weight:700;color:var(--primary);background:var(--tag-bg);padding:2px 5px;border-radius:4px;">${icon}</span></div>
        <div class="file-item-info">
          <div class="file-item-name">${escapeHtml(f.name)}</div>
          <div class="file-item-meta">${size}</div>
        </div>
        <button type="button" class="file-item-remove" onclick="removeFile(${i})" aria-label="Remove ${escapeHtml(f.name)}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      `;
      fileList.appendChild(item);
    });
    updateFileInput();
  }

  window.removeFile = function(idx) {
    selectedFiles.splice(idx, 1);
    renderFiles();
  };

  function updateFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
})();
</script>
JS;
require __DIR__ . '/../layouts/footer.php';
?>
