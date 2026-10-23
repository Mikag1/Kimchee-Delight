<?php
// All formulas live here so the dashboard and API use the same math.
function metrics(array $r): array {
    $c = (int)$r['customers'];
    $meat = (float)$r['pork_kg'] + (float)$r['beef_kg'] + (float)$r['chicken_kg'] + (float)$r['other_kg'];
    $rev = (float)$r['revenue']; $mc = (float)$r['meat_cost']; $ox = (float)$r['other_expenses'];
    $profit = $rev - $mc - $ox;
    return array_merge($r, [
        'customers' => $c, 'revenue' => $rev, 'meat_cost' => $mc, 'other_expenses' => $ox,
        'pork_kg' => (float)$r['pork_kg'], 'beef_kg' => (float)$r['beef_kg'],
        'chicken_kg' => (float)$r['chicken_kg'], 'other_kg' => (float)$r['other_kg'],
        'total_meat_kg' => round($meat, 2),
        'avg_meat_kg' => $c > 0 ? $meat / $c : null,      // null = cannot divide by zero
        'avg_cost' => $c > 0 ? $mc / $c : null,
        'avg_revenue' => $c > 0 ? $rev / $c : null,
        'net_profit' => $profit,
        'margin' => $rev > 0 ? $profit / $rev * 100 : null,
    ]);
}

function summarize(array $rows): array {
    $t = ['customers'=>0,'pork_kg'=>0,'beef_kg'=>0,'chicken_kg'=>0,'other_kg'=>0,'revenue'=>0,'meat_cost'=>0,'other_expenses'=>0];
    foreach ($rows as $r) foreach ($t as $k => $_) $t[$k] += (float)$r[$k];
    return ['records' => count($rows)] + metrics($t);
}
