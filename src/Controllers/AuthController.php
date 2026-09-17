<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Repositories\UserRepository;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService(new UserRepository());
    }

    public function loginForm(): void
    {
        AuthMiddleware::guest();
        $flash = getFlash();
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        AuthMiddleware::guest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }

        if (!validateCsrf()) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirect('login');
        }

        $email = sanitizeString($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlash('error', 'Email and password are required.');
            redirect('login');
        }

        $user = $this->authService->login($email, $password);
        if (!$user) {
            setFlash('error', 'Invalid email or password.');
            redirect('login');
        }

        // Set session (regenerate ID to prevent session fixation)
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user'] = $user;

        redirect('home');
    }

    public function devLogin(): void
    {
        AuthMiddleware::guest();

        if (!isDevEnvironment()) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        $user = $this->authService->devLogin();
        if (!$user) {
            setFlash('error', 'Dev login failed. No users in database.');
            redirect('login');
        }

        unset($user['password_hash']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user'] = $user;

        redirect('home');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_regenerate_id(true);
        session_destroy();
        header('Location: ' . url('/login'));
        exit;
    }
}
