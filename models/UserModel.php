<?php
namespace App\models;

use PDO;

class UserModel
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo; // from config/database.php
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
