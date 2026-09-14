<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Repositories\WorkReportRepository;

class FileController
{
    public function serve($fileId): void
    {
        AuthMiddleware::check();
        $fileId = (int) $fileId;
        $userId = currentUserId();

        $repo = new WorkReportRepository();
        $file = $repo->getFileById($fileId, $userId);

        if (!$file || empty($file['file_data'])) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }

        $isImage = str_starts_with($file['mime_type'], 'image/');

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . $file['file_size']);

        if ($isImage) {
            header('Content-Disposition: inline; filename="' . $file['original_filename'] . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
        }

        header('Cache-Control: private, max-age=3600');
        echo $file['file_data'];
        exit;
    }
}
