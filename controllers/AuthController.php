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

        $redirect = $_SESSION['intended'] ?? $this->defaultHomeForRole($user['role']);

        session_regenerate_id(true);
        unset($_SESSION['intended']);

        $_SESSION['user'] = [
            // Keep both keys for compatibility across the codebase.
            'user_id' => (int)$user['user_id'],
            'id' => (int)$user['user_id'],
            'role' => $user['role'],
            'private_operator_id' => $user['private_operator_id'] ?? null,
            'sltb_depot_id' => $user['sltb_depot_id'] ?? null,
            'timekeeper_point' => $user['timekeeper_point'] ?? 'start',
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'email' => $user['email'] ?? null,
            'phone' => $user['phone'] ?? null,
        ];

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
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $pwd      = $_POST['password'] ?? '';
        $pwd2     = $_POST['confirm_password'] ?? '';

        if ($firstName === '' || $lastName === '' || $email === '' || $pwd === '' || $pwd2 === '') {
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
            'first_name' => $firstName,
            'last_name'  => $lastName,
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
            'user_id' => (int)$userId,
            'id'      => (int)$userId,
            'role'    => 'Passenger',
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'   => $email,
            'phone'   => ($phone !== '' ? $phone : null),
        ];

        header('Location: ' . $this->defaultHomeForRole('Passenger'));
        exit;
    }

    /** Logout */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        session_destroy();
        header("Location: /login");
        exit;
    }

    protected function defaultHomeForRole(?string $role): string
    {
        return match ($role) {
            'NTCAdmin'          => '/A/dashboard',
            'DepotManager'      => '/M',
            'DepotOfficer'      => '/O',
            'PrivateBusOwner'   => '/B',
            'SLTBTimekeeper'    => '/TS',
            'PrivateTimekeeper' => '/TP',
            'Passenger'         => '/home',
            default             => '/home',
        };
    }
}
