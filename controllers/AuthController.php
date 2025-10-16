<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\UserModel; // same as your login import

class AuthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('guest');
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
            header("Location: /login?error=Invalid credentials");
            exit;
        }

        if ($user['status'] !== 'Active') {
            header("Location: /login?error=Account inactive");
            exit;
        }

        $_SESSION['user'] = [
            'id'    => $user['user_id'],
            'role'  => $user['role'],
            'name'  => $user['full_name'],
            'email' => $user['email']
        ];

        $redirect = $_SESSION['intended'] ?? $this->defaultHomeForRole($user['role']);
        unset($_SESSION['intended']);
        header("Location: $redirect");
        exit;
    }

    /** Passenger sign-up (GET shows form, POST creates account) */
    public function register(): void
    {
        // GET → show the same-styled form
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->view('auth', 'register', [
                'error' => $_GET['error'] ?? null
            ]);
            return;
        }

        // POST → create passenger user
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $pwd      = $_POST['password'] ?? '';
        $pwd2     = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $pwd === '' || $pwd2 === '') {
            header('Location: /register?error=All fields are required');
            exit;
        }
        if ($pwd !== $pwd2) {
            header('Location: /register?error=Passwords do not match');
            exit;
        }

        $um = new UserModel();
        if ($um->findByEmail($email)) {
            header('Location: /register?error=Email already in use');
            exit;
        }

        $userId = $um->createPassenger([
            'full_name' => $fullName,
            'email'     => $email,
            'phone'     => $phone !== '' ? $phone : null,
            'password'  => $pwd
        ]);

        if (!$userId) {
            header('Location: /register?error=Could not create account');
            exit;
        }

        // auto-login new passenger
        $_SESSION['user'] = [
            'id'    => $userId,
            'role'  => 'Passenger',
            'name'  => $fullName,
            'email' => $email
        ];

        header('Location: ' . $this->defaultHomeForRole('Passenger'));
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
            'NTCAdmin'          => '/A/dashboard',
            'DepotManager'      => '/M',
            'DepotOfficer'      => '/O',
            'PrivateBusOwner'   => '/P',
            'SLTBTimekeeper'    => '/TS',
            'PrivateTimekeeper' => '/TP',
            'Passenger'         => '/home',
            default             => '/home',
        };
    }
}
