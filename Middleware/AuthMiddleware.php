<?php
namespace App\Middleware;

class AuthMiddleware
{
    private static function defaultHomeForRole(?string $role): string
    {
        return match ($role) {
            'NTCAdmin'          => '/A/dashboard',
            'DepotManager'      => '/M',
            'DepotOfficer'      => '/O',
            'PrivateBusOwner'   => '/B',
            'SLTBTimekeeper'    => '/TS',
            'PrivateTimekeeper' => '/TP',
            'Passenger'         => '/home',
            default             => '/login',
        };
    }

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
            header('Location: ' . self::defaultHomeForRole($_SESSION['user']['role'] ?? null));
            exit;
        }
    }
}
