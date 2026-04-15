<?php
namespace App\models\bus_owner;

use App\models\bus_owner\BaseModel;
use PDO;

/**
 * Messaging model for the Bus Owner portal.
 * Sends notifications to PrivateTimekeeper users under the same operator.
 */
class MessageModel extends BaseModel
{
    private array $columnCache = [];

    private function columnExists(string $table, string $col): bool
    {
        $key = "{$table}:{$col}";
        if (array_key_exists($key, $this->columnCache)) return $this->columnCache[$key];
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?"
        );
        $st->execute([$table, $col]);
        return $this->columnCache[$key] = ((int)$st->fetchColumn() > 0);
    }

    /** All PrivateTimekeeper user_ids linked to this operator. */
    public function timekeeperUserIds(): array
    {
        if (!$this->hasOperator()) return [];
        $st = $this->pdo->prepare(
            "SELECT user_id FROM users
             WHERE role='PrivateTimekeeper' AND private_operator_id=? AND status='Active'"
        );
        $st->execute([$this->operatorId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Send a message to all timekeepers under this operator (or a specific user_id subset).
     */
    public function send(string $text, string $priority='normal', ?string $category=null, array $toUserIds=[]): bool
    {
        $text = trim($text);
        if (!$text || !$this->hasOperator()) return false;

        $recipients = $toUserIds ?: $this->timekeeperUserIds();
        if (!$recipients) return false;

        // Resolve sender name from session
        $u = $_SESSION['user'] ?? [];
        $senderName = trim((string)(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
        if ($senderName === '') $senderName = 'Bus Owner';

        $metadata = json_encode([
            'source'         => 'owner_message',
            'source_user_id' => (int)($u['user_id'] ?? 0) ?: null,
            'source_role'    => 'PrivateBusOwner',
            'source_name'    => $senderName,
            'category'       => $category,
        ], JSON_UNESCAPED_UNICODE);

        $hasPriority = $this->columnExists('notifications', 'priority');
        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $hasCategory = $this->columnExists('notifications', 'category');

        $cols = ['user_id', 'type', 'message', 'is_seen'];
        $vals = ['?', '?', '?', '0'];
        if ($hasPriority) { $cols[] = 'priority';  $vals[] = '?'; }
        if ($hasMetadata) { $cols[] = 'metadata';  $vals[] = '?'; }
        if ($hasCategory) { $cols[] = 'category';  $vals[] = '?'; }
        $cols[] = 'created_at';
        $vals[] = 'NOW()';

        $ins = $this->pdo->prepare(
            'INSERT INTO notifications(' . implode(',', $cols) . ') VALUES(' . implode(',', $vals) . ')'
        );

        try {
            $this->pdo->beginTransaction();
            foreach ($recipients as $uid) {
                $params = [(int)$uid, 'Message', $text];
                if ($hasPriority) $params[] = $priority;
                if ($hasMetadata) $params[] = $metadata;
                if ($hasCategory) $params[] = $category;
                $ins->execute($params);
            }
            return $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }

    /** Recent sent messages (last N notifications sent by this owner's user_id). */
    public function sentHistory(int $limit = 30): array
    {
        $u   = $_SESSION['user'] ?? [];
        $uid = (int)($u['user_id'] ?? 0);
        if ($uid <= 0 || !$this->hasOperator()) return [];

        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $hasCategory = $this->columnExists('notifications', 'category');
        $hasPriority = $this->columnExists('notifications', 'priority');

        $categorySelect = $hasCategory ? 'n.category'
            : ($hasMetadata ? "JSON_UNQUOTE(JSON_EXTRACT(n.metadata,'$.category')) AS category" : "NULL AS category");
        $prioritySelect = $hasPriority ? 'n.priority' : "'normal' AS priority";

        if ($hasMetadata) {
            // Find all notifications sent by this owner (source_user_id in metadata)
            $sql = "SELECT DISTINCT n.id, n.type, n.message, n.created_at,
                           {$prioritySelect}, {$categorySelect},
                           COUNT(n2.id) AS recipient_count
                    FROM notifications n
                    JOIN notifications n2 ON
                        n2.message=n.message AND n2.created_at=n.created_at
                        AND n2.type=n.type
                    WHERE JSON_UNQUOTE(JSON_EXTRACT(n.metadata,'$.source_user_id')) = ?
                    GROUP BY n.id, n.type, n.message, n.created_at
                    ORDER BY n.created_at DESC
                    LIMIT {$limit}";
            try {
                $st = $this->pdo->prepare($sql);
                $st->execute([(string)$uid]);
                return $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                return [];
            }
        }
        return [];
    }
}
