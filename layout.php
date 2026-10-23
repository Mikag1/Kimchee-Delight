<?php
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function page_start(string $title, string $active): void {
    $nav = [['dashboard','Dashboard','layout-dashboard'],['consumption','Consumption','scale'],
            ['queue','Queue','users'],['orders','Orders','qr-code'],['inventory','Inventory','package']];
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?> · Kimchee Delight Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=3">
<script src="https://unpkg.com/lucide@0.383.0/dist/umd/lucide.min.js"></script>
</head><body>
<aside class="sidebar" id="sidebar">
  <div class="brand"><span class="logo">火</span><div class="brand-copy"><b>Kimchee Delight Admin</b><small>KOREAN BBQ · OPERATIONS</small></div></div>
  <nav>
  <?php foreach ($nav as [$file, $label, $icon]): ?>
    <a href="<?= $file ?>.php" class="<?= $file === $active ? 'on' : '' ?>"><i data-lucide="<?= $icon ?>"></i><span><?= $label ?></span></a>
  <?php endforeach; ?>
  </nav>
</aside>
<main class="main">
  <header class="top">
    <button class="icon-btn" id="toggle" aria-label="Toggle sidebar"><i data-lucide="menu"></i></button>
    <h1><?= esc($title) ?></h1>
    <button class="icon-btn theme-toggle" id="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode" aria-pressed="false"><i data-lucide="moon"></i></button>
  </header>
  <div class="content">
<?php }

function page_end(string $script = ''): void { ?>
  </div>
</main>
<script src="assets/js/app.js"></script>
<?php if ($script): ?><script src="assets/js/<?= esc($script) ?>"></script><?php endif; ?>
</body></html>
<?php }
