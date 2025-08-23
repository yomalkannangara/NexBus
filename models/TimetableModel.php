<?php
require_once __DIR__.'/BaseModel.php';
class TimetableModel extends BaseModel {
    public function all(): array {
        $sql = "SELECT t.*, r.route_no FROM timetables t JOIN routes r ON r.route_id=t.route_id
                ORDER BY r.route_no+0, r.route_no, t.day_of_week, t.departure_time";
        return $this->pdo->query($sql)->fetchAll();
    }
     public function counts(): array {
        $depots = (int)$this->pdo->query("SELECT COUNT(*) c FROM routes")->fetch()['c'];
        $routes = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_depots")->fetch()['c'];
        $powners = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_bus_owners")->fetch()['c'];
        $sbus = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_buses")->fetch()['c'];
        $pbus = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_buses")->fetch()['c'];

        return compact('depots','routes','pbus','sbus','powners');
    }
    public function routes(): array {
        return $this->pdo
            ->query("SELECT route_id, route_no, name, is_active, stops_json, start_seq, end_seq
                    FROM routes ORDER BY route_no+0, route_no")
            ->fetchAll();
    }

    public function ownersWithBuses(): array {
        $owners = $this->pdo->query("SELECT private_operator_id, name FROM private_bus_owners ORDER BY name")->fetchAll();
        $buses = $this->pdo->query("SELECT reg_no, private_operator_id FROM private_buses ORDER BY reg_no")->fetchAll();
        $map = [];
        foreach ($owners as $o) { $map[$o['private_operator_id']] = ['id'=>$o['private_operator_id'], 'name'=>$o['name'], 'buses'=>[]]; }
        foreach ($buses as $b) { if (isset($map[$b['private_operator_id']])) { $map[$b['private_operator_id']]['buses'][] = $b['reg_no']; } }
        return array_values($map);
    }
    public function depotsWithBuses(): array {
        $depots = $this->pdo->query("SELECT sltb_depot_id, name FROM sltb_depots ORDER BY name")->fetchAll();
        $buses = $this->pdo->query("SELECT reg_no, sltb_depot_id FROM sltb_buses ORDER BY reg_no")->fetchAll();
        $map = [];
        foreach ($depots as $d) { $map[$d['sltb_depot_id']] = ['id'=>$d['sltb_depot_id'], 'name'=>$d['name'], 'buses'=>[]]; }
        foreach ($buses as $b) { if (isset($map[$b['sltb_depot_id']])) { $map[$b['sltb_depot_id']]['buses'][] = $b['reg_no']; } }
        return array_values($map);
    }

    public function create(array $d): void {
        $sql = "INSERT INTO timetables (operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time, start_seq, end_seq, effective_from, effective_to)
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['operator_type'], $d['route_id'], trim($d['bus_reg_no']), $d['day_of_week'],
            $d['departure_time'], $d['arrival_time'] ?: null, $d['start_seq'] ?: null, $d['end_seq'] ?: null,
            $d['effective_from'] ?: null, $d['effective_to'] ?: null
        ]);
    }
    public function createRoute(array $d): void {
        $st = $this->pdo->prepare(
            "INSERT INTO routes (route_no, name, is_active, stops_json, start_seq, end_seq)
            VALUES (?,?,?,?,?,?)"
        );
        $st->execute([
            $d['route_no'],
            $d['name'] ?? null,
            $d['is_active'] ?? 1,
            $d['stops_json'] ?: '[]',
            $d['start_seq'] ?: null,
            $d['end_seq'] ?: null
        ]);
    }

    public function createDepot(array $d): void {
        $st = $this->pdo->prepare("INSERT INTO sltb_depots (name, city, phone) VALUES (?,?,?)");
        $st->execute([
            $d['name'],
            $d['city'] ?: null,
            $d['phone'] ?: null
        ]);
    }

    public function delete($id): void {
        $st = $this->pdo->prepare("DELETE FROM timetables WHERE timetable_id=?");
        $st->execute([$id]);
    }
}
?>