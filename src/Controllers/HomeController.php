<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Repositories\WorkReportRepository;
use App\Services\WorkReportService;
use App\Services\FileStorageService;

class HomeController
{
    private WorkReportService $reportService;

    public function __construct()
    {
        $repo = new WorkReportRepository();
        $fileService = new FileStorageService($repo);
        $this->reportService = new WorkReportService($repo, $fileService);
    }

    public function index(): void
    {
        AuthMiddleware::check();
        $userId = currentUserId();
        $user = currentUser();

        $searchQuery = trim($_GET['q'] ?? '');
        $searchDate = trim($_GET['date'] ?? '');

        if (!empty($searchQuery) || !empty($searchDate)) {
            $reports = $this->reportService->searchUserReports($userId, $searchQuery, $searchDate);
        } else {
            $reports = $this->reportService->getUserReports($userId);
        }

        require __DIR__ . '/../Views/home/index.php';
    }
}
