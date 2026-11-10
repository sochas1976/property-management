<?php
namespace App\Core;

class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $user): void {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'email'     => $user['email'],
            'full_name' => $user['full_name'],
            'role'      => $user['role']
        ];
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string {
        return self::user()['role'] ?? null;
    }

    /**
     * @param string|array $roles
     */
    public static function hasRole($roles): bool {
        if (!self::check()) {
            return false;
        }
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            $_SESSION['flash_error'] = 'Please log in to continue.';
            header('Location: ' . Router::url('/login'));
            exit;
        }
    }

    /**
     * @param string|array $roles
     */
    public static function requireRole($roles): void {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #f87171;background:#fef2f2;color:#991b1b;border-radius:8px;'>"
                . "<h2>403 - Unauthorized Access</h2>"
                . "<p>You do not have permission to view this resource.</p>"
                . "<p><a href='" . Router::url('/dashboard') . "' style='color:#dc2626;'>Return to Dashboard</a></p>"
                . "</div>");
        }
    }

    public static function csrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        self::start();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
