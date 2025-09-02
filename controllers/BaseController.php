<?php
namespace App\controllers;

class BaseController
{
    // TEMP shim: lets old code keep working
protected function view(string $module, string $page, array $data = []): void {
  $this->render("{$module}/{$page}", $data);
}

    protected string $viewsPath;
    protected ?array $user = null;          // session user (role, ids, etc.)
    protected string $layout = 'default';   // /views/layouts/default.php

    public function __construct()
    {

        $this->viewsPath = __DIR__ . '/../views/';
        $this->user      = $_SESSION['user'] ?? null;

    }

    /** Optional: role → layout mapper */
    protected function mapRoleToLayout(string $role): string
    {
        return match ($role) {
            'guest'                                            => 'guest',
            'NTCAdmin','DepotManager'                          => 'admin',
            'DepotOfficer','SLTBTimekeeper','PrivateTimekeeper'=> 'staff',
            'PrivateBusOwner'                                  => 'owner',
            'Passenger'                                        => 'passenger',
            default                                            => 'default',
        };
    }

    /** Manually pick a layout from controllers */
    protected function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }


    protected function render(string $view, array $data = [], string|false|null $layout = null): void
    {
        $viewFile   = $this->viewsPath . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        // expose $data in both view and layout
        extract($data, EXTR_SKIP);

        // Raw view (no layout)
        if ($layout === false) {
            require $viewFile;
            return;
        }

        $layoutName = $layout ?? $this->layout;
        $layoutFile = $this->viewsPath . 'layouts/' . $layoutName . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layoutName}");
        }

        // Hand the view path to the layout; the layout will `require $contentViewFile`
        $contentViewFile = $viewFile;
        require $layoutFile;
    }

    /** Auth guard */
    protected function requireLogin(array $roles = []): void
    {
        if (!$this->user) {
            header('Location: /login.php'); exit;
        }
        if ($roles && !in_array($this->user['role'] ?? '', $roles, true)) {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1>";
            exit;
        }
    }

    /** Optional helpers (safe to delete if unused) */
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
