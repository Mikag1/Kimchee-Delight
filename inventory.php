<?php
require __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function inventory_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function inventory_quantity($value, string $label, float $minimum = 0): float {
    if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < $minimum || (float)$value > 999999999) {
        inventory_out(['errors' => ["$label must be a valid quantity of $minimum or more."]], 422);
    }
    return round((float)$value, 3);
}

function inventory_date($value): string {
    $date = (string)$value;
    $parts = explode('-', $date);
    if (count($parts) !== 3 || !checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
        inventory_out(['errors' => ['Enter a valid movement date.']], 422);
    }
    return $date;
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $items = $pdo->query('SELECT id, name, category AS cat, unit, quantity AS qty, low_stock AS low, is_sample
                              FROM inventory_items ORDER BY name')->fetchAll();
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['qty'] = (float)$item['qty'];
            $item['low'] = (float)$item['low'];
            $item['is_sample'] = (int)$item['is_sample'];
        }
        unset($item);

        $movements = $pdo->query('SELECT m.id, m.item_id AS itemId, i.name AS itemName, m.movement_date AS date,
                                         m.added, m.used, m.adjustment, m.balance_after AS balance, m.note
                                  FROM inventory_movements m
                                  JOIN inventory_items i ON i.id = m.item_id
                                  ORDER BY m.id DESC LIMIT 200')->fetchAll();
        foreach ($movements as &$movement) {
            $movement['id'] = (int)$movement['id'];
            $movement['itemId'] = (int)$movement['itemId'];
            foreach (['added', 'used', 'adjustment', 'balance'] as $field) $movement[$field] = (float)$movement[$field];
        }
        unset($movement);
        $date = inventory_date($_GET['date'] ?? date('Y-m-d'));
        $statement = $pdo->prepare('SELECT i.id AS itemId, i.name AS itemName, SUM(m.added) AS added, SUM(m.used) AS used
                                    FROM inventory_movements m JOIN inventory_items i ON i.id = m.item_id
                                    WHERE m.movement_date = ? GROUP BY i.id, i.name ORDER BY i.name');
        $statement->execute([$date]);
        $daily = $statement->fetchAll();
        foreach ($daily as &$entry) {
            $entry['itemId'] = (int)$entry['itemId'];
            $entry['added'] = (float)$entry['added'];
            $entry['used'] = (float)$entry['used'];
        }
        unset($entry);
        inventory_out(['items' => $items, 'movements' => $movements, 'dailyUsage' => $daily]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) inventory_out(['errors' => ['Invalid request body.']], 400);
    $action = $input['action'] ?? '';
    if (!in_array($method, ['POST', 'PUT'], true)) inventory_out(['errors' => ['Unsupported request.']], 405);

    if ($method === 'POST' && $action === 'add_item') {
        $name = trim((string)($input['name'] ?? ''));
        $category = trim((string)($input['category'] ?? 'Other'));
        $unit = trim((string)($input['unit'] ?? ''));
        if ($name === '' || strlen($name) > 80 || $category === '' || strlen($category) > 30 || $unit === '' || strlen($unit) > 20) {
            inventory_out(['errors' => ['Enter a name, category, and unit within the allowed length.']], 422);
        }
        $quantity = inventory_quantity($input['quantity'] ?? null, 'Opening stock');
        $low = inventory_quantity($input['low'] ?? 0, 'Low-stock level');
        $pdo->beginTransaction();
        $statement = $pdo->prepare('INSERT INTO inventory_items (name, category, unit, quantity, low_stock, is_sample) VALUES (?, ?, ?, ?, ?, 0)');
        $statement->execute([$name, $category, $unit, $quantity, $low]);
        $id = (int)$pdo->lastInsertId();
        if ($quantity > 0) {
            $statement = $pdo->prepare("INSERT INTO inventory_movements
                (item_id, movement_type, adjustment, balance_after, note, movement_date)
                VALUES (?, 'opening', ?, ?, 'Opening stock', ?)");
            $statement->execute([$id, $quantity, $quantity, inventory_date($input['date'] ?? date('Y-m-d'))]);
        }
        $pdo->commit();
        inventory_out(['ok' => true, 'id' => $id], 201);
    }

    $itemId = filter_var($input['itemId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($itemId === false) inventory_out(['errors' => ['Choose a valid inventory item.']], 422);

    if ($method === 'PUT' && $action === 'details') {
        $unit = trim((string)($input['unit'] ?? ''));
        if ($unit === '' || strlen($unit) > 20) inventory_out(['errors' => ['Enter a unit within 20 characters.']], 422);
        $low = inventory_quantity($input['low'] ?? null, 'Low-stock level');
        $statement = $pdo->prepare('UPDATE inventory_items SET unit = ?, low_stock = ? WHERE id = ?');
        $statement->execute([$unit, $low, $itemId]);
        if (!$statement->rowCount()) {
            $exists = $pdo->prepare('SELECT 1 FROM inventory_items WHERE id = ?');
            $exists->execute([$itemId]);
            if (!$exists->fetchColumn()) inventory_out(['errors' => ['Inventory item not found.']], 404);
        }
        inventory_out(['ok' => true]);
    }

    if ($method === 'POST' && !in_array($action, ['movement', 'count'], true)) {
        inventory_out(['errors' => ['Invalid inventory action.']], 422);
    }
    if ($method === 'PUT' || !in_array($action, ['movement', 'count'], true)) {
        inventory_out(['errors' => ['Unsupported inventory update.']], 405);
    }

    $date = inventory_date($input['date'] ?? date('Y-m-d'));
    $note = trim((string)($input['note'] ?? ''));
    if (strlen($note) > 255) inventory_out(['errors' => ['Movement note must be 255 characters or less.']], 422);

    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT quantity FROM inventory_items WHERE id = ? FOR UPDATE');
    $statement->execute([$itemId]);
    $current = $statement->fetchColumn();
    if ($current === false) {
        $pdo->rollBack();
        inventory_out(['errors' => ['Inventory item not found.']], 404);
    }
    $current = (float)$current;
    $added = 0.0;
    $used = 0.0;
    $adjustment = 0.0;
    if ($action === 'count') {
        $balance = inventory_quantity($input['quantity'] ?? null, 'Physical count');
        $adjustment = round($balance - $current, 3);
        $movementType = 'count';
        if ($note === '') $note = 'Physical stock count';
    } else {
        $quantity = inventory_quantity($input['quantity'] ?? null, 'Movement quantity', 0.001);
        $type = $input['type'] ?? '';
        if ($type === 'received') {
            $added = $quantity;
            $movementType = 'received';
        } elseif ($type === 'used') {
            $used = $quantity;
            $movementType = 'used';
            if ($used > $current) {
                $pdo->rollBack();
                inventory_out(['errors' => ["Only $current available; stock cannot go below zero."]], 422);
            }
        } else {
            $pdo->rollBack();
            inventory_out(['errors' => ['Choose received or used.']], 422);
        }
        $balance = round($current + $added - $used, 3);
    }

    $statement = $pdo->prepare('UPDATE inventory_items SET quantity = ?, is_sample = CASE WHEN ? = ? THEN 0 ELSE is_sample END WHERE id = ?');
    $statement->execute([$balance, $movementType, 'count', $itemId]);
    $statement = $pdo->prepare('INSERT INTO inventory_movements
        (item_id, movement_type, added, used, adjustment, balance_after, note, movement_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([$itemId, $movementType, $added, $used, $adjustment, $balance, $note, $date]);
    $pdo->commit();
    inventory_out(['ok' => true, 'balance' => $balance]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ((string)$e->getCode() === '23000') inventory_out(['errors' => ['An inventory item with that name already exists.']], 409);
    inventory_out(['errors' => ['Inventory service database error. Run database/inventory_orders.sql and check config/database.php.']], 500);
}
