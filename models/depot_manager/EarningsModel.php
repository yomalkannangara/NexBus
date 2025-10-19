<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class EarningsModel extends BaseModel
{
    public function top(): array
    {
        try {
            $sql = "SELECT b.reg_no, SUM(e.amount) total
                      FROM earnings e
                      JOIN buses b ON b.id=e.bus_id
                  WHERE YEAR(e.date)=YEAR(CURDATE()) AND MONTH(e.date)=MONTH(CURDATE())
                  GROUP BY b.id, b.reg_no
                  ORDER BY total DESC
                  LIMIT 10";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function busIncome(): array
    {
        try {
            $sql = "SELECT b.reg_no, SUM(e.amount) AS total
                      FROM earnings e
                      JOIN buses b ON b.id=e.bus_id
                  WHERE YEAR(e.date)=YEAR(CURDATE()) AND MONTH(e.date)=MONTH(CURDATE())
                  GROUP BY b.id, b.reg_no
                  ORDER BY b.reg_no";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function monthlySummary(): array
    {
        try {
            $sql = "SELECT DATE(e.date) as date, SUM(e.amount) as total
                      FROM earnings e
                  WHERE YEAR(e.date)=YEAR(CURDATE()) AND MONTH(e.date)=MONTH(CURDATE())
                  GROUP BY DATE(e.date)
                  ORDER BY DATE(e.date)";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function add(array $d): bool
    {
        try {
            $sql = "INSERT INTO earnings (bus_id, date, amount, source, created_at)
                    VALUES (:bus_id, :date, :amount, :source, NOW())";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':bus_id' => (int)($d['bus_id'] ?? 0),
                ':date'   => $d['date'] ?? date('Y-m-d'),
                ':amount' => (float)($d['amount'] ?? 0),
                ':source' => $d['source'] ?? 'Ticketing',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM earnings WHERE id=?");
            return $st->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function importCsv(?array $file): bool
    {
        if (!$file || !is_uploaded_file($file['tmp_name'] ?? '')) return false;

        $okAll = true;
        if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
            // Optional: skip header
            $first = true;
            while (($row = fgetcsv($handle)) !== false) {
                // Expect columns: bus_id,date,amount,source
                if ($first && $this->looksLikeHeader($row)) { $first = false; continue; }
                $first = false;
                $ok = $this->add([
                    'bus_id' => (int)($row[0] ?? 0),
                    'date'   => $row[1] ?? date('Y-m-d'),
                    'amount' => (float)($row[2] ?? 0),
                    'source' => $row[3] ?? 'Ticketing',
                ]);
                if (!$ok) $okAll = false;
            }
            fclose($handle);
        }
        return $okAll;
    }

    private function looksLikeHeader(array $row): bool
    {
        $joined = strtolower(implode(',', $row));
        return str_contains($joined, 'bus') || str_contains($joined, 'amount');
    }
}
