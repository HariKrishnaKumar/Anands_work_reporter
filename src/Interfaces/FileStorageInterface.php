<?php
declare(strict_types=1);

namespace App\Interfaces;

interface FileStorageInterface
{
    /**
     * Store an uploaded file
     * @return array{stored_filename: string, storage_path: string}
     */
    public function store(array $file, int $userId): array;

    /**
     * Get the full filesystem path for a stored file
     */
    public function getFullPath(string $storagePath): string;

    /**
     * Check if a stored file exists
     */
    public function exists(string $storagePath): bool;

    /**
     * Delete a stored file
     */
    public function delete(string $storagePath): bool;

    /**
     * Get the MIME type of a stored file
     */
    public function getMimeType(string $storagePath): string;
}
