<?php
namespace App\models\Passenger;

use PDO;

abstract class BaseModel {
  protected PDO $pdo;
  public function __construct() {
    $this->pdo = $GLOBALS['db'];   
  }
}

class NotificationsModel extends BaseModel {
  /**
   * Simple, flexible listing.
   * - $filter can be a string tab: 'all' | 'delays' | 'alerts'
   * - or an array: ['type' => 'Delay'] or ['unread' => true]
   */
  public function listForUser(int $userId, $filter = null, int $limit = 100): array {
    $where = ["user_id = :uid"];
    $params = [':uid' => $userId];

    // Allow simple string tabs
    if (is_string($filter)) {
      $tab = strtolower($filter);
      if ($tab === 'delays') {
        $where[] = "type = 'Delay'";
      } elseif ($tab === 'alerts') {
        $where[] = "is_seen = 0";
      }
    } elseif (is_array($filter) && $filter) {
      if (!empty($filter['type'])) {
        $where[] = "type = :type";
        $params[':type'] = $filter['type'];
      }
      if (!empty($filter['unread'])) {
        $where[] = "is_seen = 0";
      }
    }

    $sql = "SELECT id, type, message, is_seen, created_at
            FROM notifications
            WHERE " . implode(' AND ', $where) . "
            ORDER BY created_at DESC, id DESC
            LIMIT :lim";

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // decorate for UI: tag and human age string
    foreach ($rows as &$r) {
      $r['tag'] = $this->tagForType($r['type'] ?? 'System');
      $r['age'] = $this->humanAge($r['created_at'] ?? null);
    }
    unset($r);

    return $rows;
  }

  public function counts(int $userId): array {
    $sql = "SELECT
              COUNT(*) AS all_count,
              SUM(CASE WHEN type = 'Delay' THEN 1 ELSE 0 END) AS delays_count,
              SUM(CASE WHEN is_seen = 0 THEN 1 ELSE 0 END) AS unread_count
            FROM notifications
            WHERE user_id = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['all_count'=>0,'delays_count'=>0,'unread_count'=>0];
    return [
      'all' => (int)$row['all_count'],
      'delays' => (int)$row['delays_count'],
      'unread' => (int)$row['unread_count'],
    ];
  }

  public function markAllRead(int $userId): void {
    $stmt = $this->pdo->prepare("UPDATE notifications SET is_seen = 1 WHERE user_id = ? AND is_seen = 0");
    $stmt->execute([$userId]);
  }

  public function markRead(int $userId, int $id): bool {
    $stmt = $this->pdo->prepare("UPDATE notifications SET is_seen = 1 WHERE id = ? AND user_id = ? AND is_seen = 0");
    $stmt->execute([$id, $userId]);
    return $stmt->rowCount() > 0;
  }

  // --- helpers ---
  private function scalar(string $sql, array $params = []) {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    return $val === false ? null : $val;
  }

  private function tagForType(string $type): string {
    return match ($type) {
      'Delay'     => 'delay',
      'Timetable' => 'timetable',
      'Message'   => 'info',
      'Complaint' => 'complaint',
      default     => 'alert',
    };
  }

  private function humanAge(?string $ts): string {
    if (!$ts) return '';
    $t = strtotime($ts);
    if (!$t) return '';
    $diff = time() - $t;
    if ($diff < 60) return $diff . ' sec ago';
    $mins = (int) floor($diff / 60);
    if ($mins < 60) return $mins . ' min ago';
    $hrs = (int) floor($mins / 60);
    if ($hrs < 24) return $hrs . ' hour' . ($hrs === 1 ? '' : 's') . ' ago';
    $days = (int) floor($hrs / 24);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
  }
}
