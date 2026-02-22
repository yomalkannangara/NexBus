<?php
namespace App\models\common;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    public function createMessage(int $senderId, string $subject, string $body, string $scope = 'user', ?string $scopeValue = null, ?int $relatedId = null, ?string $relatedType = null): int {
        $sql = "INSERT INTO messages (sender_id, scope, scope_value, subject, body, related_type, related_id) VALUES (?,?,?,?,?,?,?)";
        $st = $this->pdo->prepare($sql);
        $st->execute([$senderId, $scope, $scopeValue, $subject, $body, $relatedType, $relatedId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function addRecipients(int $messageId, array $userIds): void {
        $sql = "INSERT INTO message_recipients (message_id, recipient_user_id) VALUES (?,?)";
        $st = $this->pdo->prepare($sql);
        foreach ($userIds as $uid) {
            $st->execute([$messageId, $uid]);
        }
    }

    public function inboxForUser(int $userId): array {
        $sql = "SELECT m.*, mr.is_read, mr.id as recipient_id, u.full_name AS sender_name, ru.full_name AS recipient_name
                  FROM messages m
                  JOIN message_recipients mr ON mr.message_id = m.message_id
                  LEFT JOIN users u ON u.user_id = m.sender_id
                  LEFT JOIN users ru ON ru.user_id = mr.recipient_user_id
                 WHERE mr.recipient_user_id = ?
              ORDER BY m.created_at DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersByIds(array $ids): array {
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->pdo->prepare("SELECT user_id, full_name, role FROM users WHERE user_id IN ($placeholders)");
        $st->execute($ids);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) $out[$r['user_id']] = $r;
        return $out;
    }

    public function markRead(int $recipientRowId): bool {
        $st = $this->pdo->prepare("UPDATE message_recipients SET is_read=1 WHERE id=?");
        return $st->execute([$recipientRowId]);
    }
}
