<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\WorkReportService;
use App\Repositories\WorkReportRepository;
use App\Services\FileStorageService;
use App\Services\GoogleSheetsSyncService;
use App\Repositories\GoogleSheetsRepository;
use App\Repositories\UserRepository;
use App\Config\GoogleSheets;

class ReportController
{
    private WorkReportService $reportService;
    private FileStorageService $fileService;

    public function __construct()
    {
        $repo = new WorkReportRepository();
        $this->fileService = new FileStorageService($repo);

        $sheetsSyncService = null;
        if (GoogleSheets::isEnabled()) {
            $sheetsSyncService = new GoogleSheetsSyncService(new GoogleSheetsRepository(), new UserRepository(), $repo);
        }

        $this->reportService = new WorkReportService($repo, $this->fileService, $sheetsSyncService);
    }

    public function add(): void
    {
        AuthMiddleware::check();
        $flash = getFlash();
        $today = (new \DateTime())->format('Y-m-d');
        require __DIR__ . '/../Views/report/add.php';
    }

    public function preview(): void
    {
        AuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('report/add');
        }

        if (!validateCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect('report/add');
        }

        $workDate = sanitizeString($_POST['work_date'] ?? '');
        $description = sanitizeString($_POST['description'] ?? '');

        $validation = $this->reportService->validate([
            'work_date' => $workDate,
            'description' => $description,
        ]);

        if (!$validation['valid']) {
            setFlash('error', implode(' ', $validation['errors']));
            redirect('report/add');
        }

        $_SESSION['report_draft'] = [
            'work_date' => $workDate,
            'description' => $description,
        ];

        // Clear previous staged files
        $_SESSION['report_files'] = [];
        $_SESSION['staged_files'] = [];

        // Stage uploaded files to persistent temp so they survive across requests
        $files = $_FILES['files'] ?? null;
        if ($files && !empty($files['tmp_name'][0])) {
            $fileValidation = $this->fileService->validateFiles($files);
            if (!empty($fileValidation['valid'])) {
                $stagedPaths = $this->fileService->stageFiles($fileValidation['valid']);
                $_SESSION['staged_files'] = $stagedPaths;

                // Store metadata for review display
                foreach ($fileValidation['valid'] as $i => $f) {
                    $_SESSION['report_files'][] = [
                        'name' => $f['name'] ?: ('file_' . ($i + 1)),
                        'size' => (int)$f['size'],
                        'mime_type' => $f['mime_type'],
                    ];
                }
            }
        }

        $draft = $_SESSION['report_draft'];
        require __DIR__ . '/../Views/report/review.php';
    }

    public function save(): void
    {
        AuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('report/add');
        }

        if (!validateCsrf()) {
            setFlash('error', 'Invalid security token.');
            redirect('report/add');
        }

        $draft = $_SESSION['report_draft'] ?? null;
        if (!$draft) {
            setFlash('error', 'No report draft found. Please start again.');
            redirect('report/add');
        }

        $userId = currentUserId();

        // Pass staged files from session explicitly
        $stagedPaths = $_SESSION['staged_files'] ?? [];
        $report = $this->reportService->saveReport(
            $userId,
            $draft['work_date'],
            $draft['description'],
            $stagedPaths
        );

        // Clear draft and staged data
        unset($_SESSION['report_draft'], $_SESSION['report_files'], $_SESSION['staged_files']);

        $_SESSION['last_report_id'] = (int)$report['id'];

        redirect('report/success');
    }

    public function success(): void
    {
        AuthMiddleware::check();
        require __DIR__ . '/../Views/report/success.php';
    }

    public function view($id): void
    {
        AuthMiddleware::check();
        $id = (int) $id;
        $userId = currentUserId();
        $report = $this->reportService->getReport($id, $userId);

        if (!$report) {
            http_response_code(404);
            require __DIR__ . '/../Views/report/not_found.php';
            return;
        }

        require __DIR__ . '/../Views/report/view.php';
    }
}
