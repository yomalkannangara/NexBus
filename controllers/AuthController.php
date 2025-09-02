<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\UserModel;

class AuthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('guest'); // uses your guest.php layout
    }

    /** Show login form */
    public function loginForm(): void
    {
        $this->view('auth', 'login', [
            'error' => $_GET['error'] ?? null
        ]);
    }

    /** Handle login submission */
    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // back to login with error
            header("Location: /login?error=Invalid credentials");
            exit;
        }

        if ($user['status'] !== 'Active') {
            header("Location: /login?error=Account inactive");
            exit;
        }

        // Save to session
        $_SESSION['user'] = [
            'id'    => $user['user_id'],
            'role'  => $user['role'],
            'name'  => $user['full_name'],
            'email' => $user['email']
        ];


        // Redirect to intended or role dashboard
        $redirect = $_SESSION['intended'] ?? $this->defaultHomeForRole($user['role']);
        unset($_SESSION['intended']);
        header("Location: $redirect");
        exit;
    }

    /** Logout */
    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
        header("Location: /login");
        exit;
    }

    private function defaultHomeForRole(string $role): string
    {
        return match ($role) {
            'NTCAdmin'        => '/A/dashboard',
            'DepotManager'    => '/M',
            'DepotOfficer'    => '/O',
            'PrivateBusOwner' => '/P',
            'SLTBTimekeeper'  => '/TS',
            'PrivateTimekeeper' => '/TP',
            'Passenger'       => '/home',
            default           => '/home',
        };
    }
}
