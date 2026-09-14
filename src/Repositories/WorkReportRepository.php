<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Interfaces\WorkReportRepositoryInterface;

class WorkReportRepository implements WorkReportRepositoryInterface
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByIdAndUser(int $reportId, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM work_reports WHERE id = ? AND user_id = ?');
        $stmt->execute([$reportId, $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM work_reports WHERE user_id = ? ORDER BY work_date DESC, created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $workDate, string $description): array
    {
        $stmt = $this->db->prepare('INSERT INTO work_reports (user_id, work_date, description) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $workDate, $description]);
        $id = (int)$this->db->lastInsertId();
        return $this->findByIdAndUser($id, $userId);
    }

    /**
     * Store file with BLOB data directly in database
     */
    public function addFileWithData(int $reportId, string $originalFilename, string $mimeType, int $fileSize, string $fileData): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO work_report_files (work_report_id, original_filename, mime_type, file_size, file_data) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$reportId, $originalFilename, $mimeType, $fileSize, $fileData]);
        $id = (int)$this->db->lastInsertId();
        $stmt2 = $this->db->prepare('SELECT id, work_report_id, original_filename, mime_type, file_size, created_at FROM work_report_files WHERE id = ?');
        $stmt2->execute([$id]);
        return $stmt2->fetch();
    }

    /**
     * Get file metadata (without BLOB data) for a report
     */
    public function getFilesByReport(int $reportId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.id, f.work_report_id, f.original_filename, f.mime_type, f.file_size, f.created_at
             FROM work_report_files f
             JOIN work_reports r ON f.work_report_id = r.id
             WHERE f.work_report_id = ? AND r.user_id = ?'
        );
        $stmt->execute([$reportId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get file with BLOB data for download/preview — verifies ownership
     */
    public function getFileById(int $fileId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT f.id, f.work_report_id, f.original_filename, f.mime_type, f.file_size, f.file_data, f.created_at
             FROM work_report_files f
             JOIN work_reports r ON f.work_report_id = r.id
             WHERE f.id = ? AND r.user_id = ?'
        );
        $stmt->execute([$fileId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Backward compat — delegates to addFileWithData
     */
    public function addFile(int $reportId, string $originalFilename, string $storedFilename, string $mimeType, int $fileSize, string $storagePath): array
    {
        // This method is kept for interface compatibility but unused in BLOB mode
        throw new \RuntimeException('Use addFileWithData() for BLOB storage');
    }
}
