<?php
namespace App\models\ntc_admin;
use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class OrgModel extends BaseModel {

    public function createDepot(array $d): void {
        // DB uses `address` as the "city" for sltb_depots in your dump
        $st = $this->pdo->prepare("INSERT INTO sltb_depots (name, address, phone) VALUES (?,?,?)");
        $st->execute([
            $d['name'] ?? null,
            ($d['city'] ?? $d['address'] ?? null) ?: null,
            ($d['phone'] ?? null) ?: null
        ]);
    }

    public function createOwner(array $d): void {
        $st = $this->pdo->prepare("
            INSERT INTO private_bus_owners (name, reg_no, contact_phone, contact_email, city)
            VALUES (?,?,?,?,?)
        ");
        $st->execute([
            $d['name'] ?? null,
            ($d['reg_no'] ?? null) ?: null,
            ($d['contact_phone'] ?? null) ?: null,
            ($d['contact_email'] ?? null) ?: null,
            ($d['city'] ?? null) ?: null,
        ]);
    }

    /** Get all SLTB depots with fleet size + manager + routes */
    public function depots(): array {
        $sql = "
            SELECT
                d.sltb_depot_id AS id,
                d.sltb_depot_id,
                d.name,
                d.address,
                d.phone,
                COUNT(DISTINCT b.reg_no) AS buses,
                (
                    SELECT CONCAT(COALESCE(um.first_name,''), ' ', COALESCE(um.last_name,''))
                    FROM users um
                    WHERE um.sltb_depot_id = d.sltb_depot_id
                      AND um.role = 'DepotManager'
                    ORDER BY um.user_id DESC
                    LIMIT 1
                ) AS manager,
                (
                    SELECT GROUP_CONCAT(DISTINCT r.route_no ORDER BY r.route_no SEPARATOR ', ')
                    FROM timetables t
                    JOIN routes r ON r.route_id = t.route_id
                    WHERE t.operator_type = 'SLTB'
                      AND t.bus_reg_no IN (
                        SELECT sb.reg_no FROM sltb_buses sb WHERE sb.sltb_depot_id = d.sltb_depot_id
                      )
                ) AS routes
            FROM sltb_depots d
            LEFT JOIN sltb_buses b 
                   ON b.sltb_depot_id = d.sltb_depot_id
            GROUP BY d.sltb_depot_id, d.name, d.address, d.phone
            ORDER BY d.name
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['city'] = $r['address'] ?? null;
            $r['routes'] = !empty($r['routes']) ? explode(', ', $r['routes']) : [];
        }
        return $rows;
    }

    /** Get all private bus owners/operators with fleet size + manager + routes */
    public function owners(): array {
        $sql = "
            SELECT
                o.private_operator_id AS id,
                o.private_operator_id,
                o.name,
                o.reg_no,
                o.city,
                o.contact_phone,
                COUNT(DISTINCT b.reg_no) AS fleet_size,
                (
                    SELECT TRIM(CONCAT(COALESCE(uo.first_name,''), ' ', COALESCE(uo.last_name,'')))
                    FROM users uo
                    WHERE uo.private_operator_id = o.private_operator_id
                      AND uo.role = 'PrivateBusOwner'
                    ORDER BY uo.user_id DESC
                    LIMIT 1
                ) AS owner_name,
                (
                    SELECT GROUP_CONCAT(DISTINCT r.route_no ORDER BY r.route_no SEPARATOR ', ')
                    FROM timetables t
                    JOIN routes r ON r.route_id = t.route_id
                    WHERE t.operator_type = 'Private'
                      AND t.bus_reg_no IN (
                        SELECT pb.reg_no FROM private_buses pb WHERE pb.private_operator_id = o.private_operator_id
                      )
                ) AS routes
            FROM private_bus_owners o
            LEFT JOIN private_buses b 
                   ON b.private_operator_id = o.private_operator_id
            GROUP BY o.private_operator_id, o.name, o.reg_no, o.city, o.contact_phone
            ORDER BY o.name
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['routes'] = !empty($r['routes']) ? explode(', ', $r['routes']) : [];
        }
        return $rows;
    }

    public function deleteDepot(int $id): void {
        $this->pdo->beginTransaction();
        try {
            // FK: sltb_buses -> sltb_depots (no ON DELETE CASCADE in dump)
            $st = $this->pdo->prepare("DELETE FROM sltb_buses WHERE sltb_depot_id = ?");
            $st->execute([$id]);

            $st = $this->pdo->prepare("DELETE FROM sltb_depots WHERE sltb_depot_id = ?");
            $st->execute([$id]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteOwner(int $id): void {
        $this->pdo->beginTransaction();
        try {
            // FK: private_buses -> private_bus_owners (no ON DELETE CASCADE in dump)
            $st = $this->pdo->prepare("DELETE FROM private_buses WHERE private_operator_id = ?");
            $st->execute([$id]);

            $st = $this->pdo->prepare("DELETE FROM private_bus_owners WHERE private_operator_id = ?");
            $st->execute([$id]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
