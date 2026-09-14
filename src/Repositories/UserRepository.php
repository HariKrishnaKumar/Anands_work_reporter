<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, email, display_name, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $username, string $email, string $displayName, string $passwordHash): array
    {
        $stmt = $this->db->prepare('INSERT INTO users (username, email, display_name, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email, $displayName, $passwordHash]);
        $id = (int)$this->db->lastInsertId();
        return $this->findById($id);
    }

    public function getFirstUser(): ?array
    {
        $stmt = $this->db->query('SELECT id, username, email, display_name FROM users ORDER BY id ASC LIMIT 1');
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
