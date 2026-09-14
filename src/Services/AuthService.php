<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Return user without password hash
        unset($user['password_hash']);
        return $user;
    }

    public function devLogin(): ?array
    {
        if (!isDevEnvironment()) {
            return null;
        }
        // Get the first user for dev login
        return $this->userRepo->getFirstUser();
    }
}
