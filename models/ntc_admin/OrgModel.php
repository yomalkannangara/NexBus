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
        $st = $this->pdo->prepare("INSERT INTO sltb_depots (name, address, phone) VALUES (?,?,?)");
        $st->execute([
            $d['name'],
            $d['address'] ?: null,
            $d['phone'] ?: null
        ]);
    }
    public function createowner(array $d): void {
        $st = $this->pdo->prepare("INSERT INTO  private_bus_owners(name, reg_no, contact_phone,contact_email) VALUES (?,?,?,?)");
        $st->execute([
            $d['name'],
            $d['reg_no'] ?: null,
            $d['contact_phone'] ?: null,
            $d['contact_email'] ?: null

        ]);
    }    
    /** Get all SLTB depots with fleet size + manager + routes */
    public function depots(): array {
        $sql = "
            SELECT d.sltb_depot_id,
                   d.name,
                   d.address,
                   d.phone,
                   COUNT(DISTINCT b.reg_no) AS buses,
                   CONCAT_WS(' ', u.first_name, u.last_name) AS manager,
                   GROUP_CONCAT(DISTINCT r.route_no ORDER BY r.route_no SEPARATOR ', ') AS routes
            FROM sltb_depots d
            LEFT JOIN sltb_buses b 
                   ON b.sltb_depot_id = d.sltb_depot_id
            LEFT JOIN users u 
                   ON u.sltb_depot_id = d.sltb_depot_id 
                  AND u.role = 'DepotManager'
            LEFT JOIN timetables t
                   ON t.bus_reg_no = b.reg_no
                  AND t.operator_type = 'SLTB'
            LEFT JOIN routes r
                   ON r.route_id = t.route_id
            GROUP BY d.sltb_depot_id, d.name, d.address, d.phone, manager
            ORDER BY d.name
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        // Convert CSV string into array for template
        foreach ($rows as &$r) {
            $r['routes'] = !empty($r['routes']) ? explode(', ', $r['routes']) : [];
        }
        return $rows;
    }

    /** Get all private bus owners/operators with fleet size + manager + routes */
    public function owners(): array {
        $sql = "
            SELECT o.private_operator_id,
                   o.name,
                   o.reg_no,
                   o.contact_phone,
                   COUNT(DISTINCT b.reg_no) AS fleet_size,
                   CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name,
                   GROUP_CONCAT(DISTINCT r.route_no ORDER BY r.route_no SEPARATOR ', ') AS routes
            FROM private_bus_owners o
            LEFT JOIN private_buses b 
                   ON b.private_operator_id = o.private_operator_id
            LEFT JOIN users u 
                   ON u.private_operator_id = o.private_operator_id 
                  AND u.role = 'PrivateBusOwner'
            LEFT JOIN timetables t
                   ON t.bus_reg_no = b.reg_no
                  AND t.operator_type = 'Private'
            LEFT JOIN routes r
                   ON r.route_id = t.route_id
            GROUP BY o.private_operator_id, o.name, o.reg_no, o.contact_phone, owner_name
            ORDER BY o.name
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['routes'] = !empty($r['routes']) ? explode(', ', $r['routes']) : [];
        }
        return $rows;
    }
}
