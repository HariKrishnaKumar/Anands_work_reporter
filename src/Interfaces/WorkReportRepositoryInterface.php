<?php
declare(strict_types=1);

namespace App\Interfaces;

interface WorkReportRepositoryInterface
{
    public function findByIdAndUser(int $reportId, int $userId): ?array;
    public function findAllByUser(int $userId): array;
    public function create(int $userId, string $workDate, string $description): array;
    public function addFileWithData(int $reportId, string $originalFilename, string $mimeType, int $fileSize, string $fileData): array;
    public function getFilesByReport(int $reportId, int $userId): array;
    public function getFileById(int $fileId, int $userId): ?array;
}
