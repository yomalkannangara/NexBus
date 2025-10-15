<?php
namespace App\Models;
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

    public static function update(array $data): bool
    {
        $db = $GLOBALS['db'];

        $allowedRoles = [
            'NTCAdmin','DepotManager','DepotOfficer','SLTBTimekeeper',
            'PrivateTimekeeper','PrivateBusOwner','Passenger'
        ];
        if (!in_array($data['role'], $allowedRoles, true)) {
            throw new \InvalidArgumentException('Invalid role');
        }

        $set = [
            'full_name = :full_name',
            'email = :email',
            'phone = :phone',
            'role = :role',
            'private_operator_id = :private_operator_id',
            'sltb_depot_id = :sltb_depot_id',
        ];
        $params = [
            ':full_name' => $data['full_name'],
            ':email' => $data['email'] !== '' ? $data['email'] : null,
            ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
            ':role' => $data['role'],
            ':private_operator_id' => $data['private_operator_id'],
            ':sltb_depot_id' => $data['sltb_depot_id'],
            ':user_id' => (int)$data['user_id'],
        ];

        if (isset($data['password']) && $data['password'] !== '') {
            $set[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE user_id = :user_id';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function setStatus(int $userId, string $status): bool
    {
        $db = $GLOBALS['db'];
        $allowed = ['Active','Suspended'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $stmt = $db->prepare('UPDATE users SET status = :status WHERE user_id = :user_id');
        return $stmt->execute([':status' => $status, ':user_id' => $userId]);
    }

    public static function delete(int $userId): bool
    {
        $db = $GLOBALS['db'];
        $stmt = $db->prepare('DELETE FROM users WHERE user_id = :user_id');
        return $stmt->execute([':user_id' => $userId]);
    }
}
