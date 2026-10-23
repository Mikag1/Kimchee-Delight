<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/calc.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

function out($data, int $code = 200) { http_response_code($code); echo json_encode($data); exit; }

function validate(array $d): array {
    $errors = []; $clean = [];
    $date = (string)($d['business_date'] ?? '');
    $p = explode('-', $date);
    if (count($p) !== 3 || !checkdate((int)$p[1], (int)$p[2], (int)$p[0])) $errors[] = 'Enter a valid business date.';
    elseif ($date > date('Y-m-d')) $errors[] = 'Business date cannot be in the future.';
    $clean['business_date'] = $date;

    $fields = ['customers'=>'Customers','pork_kg'=>'Pork (kg)','beef_kg'=>'Beef (kg)','chicken_kg'=>'Chicken (kg)',
               'other_kg'=>'Other meat (kg)','revenue'=>'Revenue','meat_cost'=>'Meat cost','other_expenses'=>'Other expenses'];
    foreach ($fields as $k => $label) {
        $v = $d[$k] ?? 0; if ($v === '' || $v === null) $v = 0;
        if (!is_numeric($v) || $v < 0) { $errors[] = "$label must be a number, 0 or higher."; continue; }
        if ($k === 'customers' && floor($v) != $v) { $errors[] = 'Customers must be a whole number.'; continue; }
        if ($v > 99999999) { $errors[] = "$label is too large."; continue; }
        $clean[$k] = $k === 'customers' ? (int)$v : round((float)$v, 2);
    }
    return [$clean, $errors];
}

try {
    $pdo = db();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        if (isset($_GET['trend'])) {   // last 7 recorded days, oldest first
            $rows = $pdo->query('SELECT * FROM daily_consumption WHERE is_sample = 0 ORDER BY business_date DESC LIMIT 7')->fetchAll();
            if (!$rows) {
                $rows = $pdo->query('SELECT * FROM daily_consumption WHERE is_sample = 1 ORDER BY business_date DESC LIMIT 7')->fetchAll();
            }
            out(['trend' => array_map('metrics', array_reverse($rows))]);
        }
        $sql = 'SELECT * FROM daily_consumption WHERE 1'; $args = [];
        if (!empty($_GET['from'])) { $sql .= ' AND business_date >= ?'; $args[] = $_GET['from']; }
        if (!empty($_GET['to']))   { $sql .= ' AND business_date <= ?'; $args[] = $_GET['to']; }
        $st = $pdo->prepare($sql . ' ORDER BY business_date DESC'); $st->execute($args);
        $rows = $st->fetchAll();
        $summaryRows = array_values(array_filter($rows, static fn($row) => (int)$row['is_sample'] === 0));
        out(['records' => array_map('metrics', $rows), 'summary' => summarize($summaryRows)]);
    }

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($_GET['id'] ?? 0);

    if ($m === 'DELETE') {
        if ($id < 1) out(['errors' => ['Missing record id.']], 422);
        $pdo->prepare('DELETE FROM daily_consumption WHERE id = ?')->execute([$id]);
        out(['ok' => true]);
    }

    if ($m === 'POST' || $m === 'PUT') {
        [$c, $errors] = validate($in);
        if ($errors) out(['errors' => $errors], 422);
        $vals = [$c['business_date'],$c['customers'],$c['pork_kg'],$c['beef_kg'],$c['chicken_kg'],$c['other_kg'],$c['revenue'],$c['meat_cost'],$c['other_expenses']];
        if ($m === 'POST') {
            $existing = $pdo->prepare('SELECT id, is_sample FROM daily_consumption WHERE business_date = ?');
            $existing->execute([$c['business_date']]);
            $row = $existing->fetch();
            if ($row && (int)$row['is_sample'] === 1) {
                $vals[] = (int)$row['id'];
                $pdo->prepare('UPDATE daily_consumption SET business_date=?,customers=?,pork_kg=?,beef_kg=?,chicken_kg=?,other_kg=?,revenue=?,meat_cost=?,other_expenses=?,is_sample=0 WHERE id=?')->execute($vals);
            } elseif ($row) {
                out(['errors' => ['A record for that date already exists. Edit it instead.']], 409);
            } else {
                $pdo->prepare('INSERT INTO daily_consumption (business_date,customers,pork_kg,beef_kg,chicken_kg,other_kg,revenue,meat_cost,other_expenses,is_sample) VALUES (?,?,?,?,?,?,?,?,?,0)')->execute($vals);
            }
        } else {
            if ($id < 1) out(['errors' => ['Missing record id.']], 422);
            $vals[] = $id;
            $pdo->prepare('UPDATE daily_consumption SET business_date=?,customers=?,pork_kg=?,beef_kg=?,chicken_kg=?,other_kg=?,revenue=?,meat_cost=?,other_expenses=?,is_sample=0 WHERE id=?')->execute($vals);
        }
        out(['ok' => true]);
    }
    out(['errors' => ['Method not allowed.']], 405);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') out(['errors' => ['A record for that date already exists. Edit it instead.']], 409);
    out(['errors' => ['Database error. Check config/database.php and that database/samgyutrack.sql was imported.']], 500);
}
