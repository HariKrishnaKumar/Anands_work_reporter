<?php
declare(strict_types=1);

namespace App\Interfaces;

interface UserRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findByEmail(string $email): ?array;
    public function findByUsername(string $username): ?array;
    public function create(string $username, string $email, string $displayName, string $passwordHash): array;
}
