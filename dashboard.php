<?php
require 'config/database.php'; require 'includes/calc.php'; require 'includes/layout.php';
$latest = null; $error = '';
try {
  $row = db()->query('SELECT * FROM daily_consumption WHERE is_sample = 0 ORDER BY business_date DESC LIMIT 1')->fetch();
  if (!$row) $row = db()->query('SELECT * FROM daily_consumption WHERE is_sample = 1 ORDER BY business_date DESC LIMIT 1')->fetch();
  $latest = $row ? metrics($row) : null;
}
catch (PDOException $e) { $error = 'Cannot connect to the database. Import database/samgyutrack.sql and check config/database.php.'; }
page_start('Dashboard', 'dashboard'); ?>
<?php if ($error): ?><div class="msg err"><?= esc($error) ?></div>
<?php elseif (!$latest): ?><div class="card">No records yet. <a href="consumption.php">Add your first daily record</a>.</div>
<?php else:
  $cards = [
    ['Total customers', $latest['customers']],
    ['Buffet revenue', '₱' . number_format($latest['revenue'], 2)],
    ['Total meat consumed', number_format($latest['total_meat_kg'], 2) . ' kg'],
    ['Avg meat / customer', $latest['avg_meat_kg'] === null ? '—' : number_format($latest['avg_meat_kg'] * 1000) . ' g'],
    ['Est. food cost / customer', $latest['avg_cost'] === null ? '—' : '₱' . number_format($latest['avg_cost'], 2)],
    ['Estimated profit', '₱' . number_format($latest['net_profit'], 2)],
  ]; $sample = (int)$latest['is_sample'] === 1; ?>
<p class="note">Showing <b><?= esc($latest['business_date']) ?></b><?= $latest['business_date'] === date('Y-m-d') ? ' (today)' : ' (latest record, nothing saved for today)' ?>
  · <span class="badge <?= $sample ? 'sample' : 'real' ?>"><?= $sample ? 'Sample data' : 'Recorded data' ?></span>
  — computed from the database.</p>
<div class="grid">
<?php foreach ($cards as [$label, $value]): ?>
  <div class="card stat"><small><?= esc($label) ?></small><b><?= esc($value) ?></b></div>
<?php endforeach; ?>
</div>
<div class="card"><h2>Average meat per customer — last 7 recorded days</h2>
  <div class="bars" id="chart"></div>
  <p class="note" style="color:#7a7167;margin:8px 0 0">Light bars = sample data · Red bars = recorded by staff.</p></div>
<script>window.addEventListener('DOMContentLoaded',()=>fetch('api/consumption.php?trend=1').then(r=>r.json()).then(d=>barChart(document.getElementById('chart'),trendRows(d.trend))));</script>
<?php endif; page_end(); ?>
