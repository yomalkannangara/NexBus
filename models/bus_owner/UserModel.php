<?php
namespace App\models\bus_owner;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class UserModel extends BaseModel
{
    protected string $table = 'users';

    /** Find a user by email; returns assoc array or null */
    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $st->execute([':email' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /** Create a user (expect password already hashed) */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (name, email, password)
                VALUES (:name, :email, :password)";
        $st = $this->pdo->prepare($sql);

        return $st->execute([
            ':name'     => $data['name'] ?? null,
            ':email'    => $data['email'] ?? null,
            ':password' => $data['password'] ?? null,
        ]);
    }
}
