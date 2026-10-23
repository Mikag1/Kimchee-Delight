<?php
require __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function orders_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $rows = $pdo->query("SELECT id, table_number AS `table`, items, order_type AS type, status,
                                    checked_items, DATE_FORMAT(created_at, '%H:%i') AS `time`
                             FROM orders ORDER BY created_at DESC, id DESC")->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['table'] = (int)$row['table'];
            $row['checkedItems'] = json_decode($row['checked_items'], true) ?: [];
            unset($row['checked_items']);
        }
        unset($row);
        orders_out(['orders' => $rows]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) orders_out(['errors' => ['Invalid request body.']], 400);

    if ($method === 'POST') {
        $requested = $input['orders'] ?? [];
        if (!is_array($requested) || !$requested || count($requested) > 2) {
            orders_out(['errors' => ['Submit one order and optionally one refill at a time.']], 422);
        }
        $clean = [];
        foreach ($requested as $order) {
            if (!is_array($order)) orders_out(['errors' => ['Invalid order.']], 422);
            $table = filter_var($order['table'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
            $type = $order['type'] ?? '';
            $items = trim((string)($order['items'] ?? ''));
            if ($table === false || !in_array($type, ['Order', 'Refill'], true) || $items === '' || strlen($items) > 2000) {
                orders_out(['errors' => ['Each order needs a valid table, type, and item list.']], 422);
            }
            $clean[] = [$table, $items, $type];
        }

        $pdo->beginTransaction();
        $insert = $pdo->prepare('INSERT INTO orders (table_number, items, order_type, status, checked_items) VALUES (?, ?, ?, ?, ?)');
        $ids = [];
        foreach ($clean as [$table, $items, $type]) {
            $insert->execute([$table, $items, $type, 'Pending', '[]']);
            $ids[] = (int)$pdo->lastInsertId();
        }
        $pdo->commit();
        orders_out(['ok' => true, 'ids' => $ids], 201);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($method !== 'PUT' || $id === false) {
        orders_out(['errors' => ['Unsupported request.']], 405);
    }

    if (($input['action'] ?? '') === 'checklist') {
        $checked = $input['checkedItems'] ?? null;
        if (!is_array($checked)) orders_out(['errors' => ['Checklist must be a list.']], 422);
        $checked = array_values(array_unique(array_filter($checked, static function ($value) {
            return filter_var($value, FILTER_VALIDATE_INT) !== false && (int)$value >= 0;
        })));
        $statement = $pdo->prepare('UPDATE orders SET checked_items = ? WHERE id = ?');
        $statement->execute([json_encode($checked), $id]);
        if (!$statement->rowCount()) {
            $exists = $pdo->prepare('SELECT 1 FROM orders WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) orders_out(['errors' => ['Order not found.']], 404);
        }
        orders_out(['ok' => true]);
    }

    if (($input['action'] ?? '') !== 'status' || !in_array($input['status'] ?? '', ['Preparing', 'Served'], true)) {
        orders_out(['errors' => ['Invalid order update.']], 422);
    }
    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
    $statement->execute([$id]);
    $current = $statement->fetchColumn();
    if ($current === false) {
        $pdo->rollBack();
        orders_out(['errors' => ['Order not found.']], 404);
    }
    $next = $input['status'];
    $allowed = ($current === 'Pending' && $next === 'Preparing')
        || ($current === 'Preparing' && $next === 'Served')
        || $current === $next;
    if (!$allowed) {
        $pdo->rollBack();
        orders_out(['errors' => ['Order status has changed. Refresh the order list and try again.']], 409);
    }
    if ($current !== $next) {
        $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $statement->execute([$next, $id]);
    }
    $pdo->commit();
    orders_out(['ok' => true]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    orders_out(['errors' => ['Order service database error. Run database/inventory_orders.sql and check config/database.php.']], 500);
}
