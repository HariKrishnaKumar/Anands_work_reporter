<?php
declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public static function check(): void
    {
        if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
            header('Location: ' . url('login'));
            exit;
        }
    }

    public static function guest(): void
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            header('Location: ' . url('home'));
            exit;
        }
    }
}