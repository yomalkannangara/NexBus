<?php
namespace App\middleware;

class AuthMiddleware
{
    public static function check(): void
    {
        if (empty($_SESSION['user'])) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'];
            header("Location: /login");
            exit;
        }
    }

    public static function requireRole(array $roles): void
    {
        self::check();
        if (!in_array($_SESSION['user']['role'], $roles)) {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1>";
            exit;
        }
    }
}
