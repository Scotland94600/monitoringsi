<?php

require_once __DIR__ . '/../app/bootstrap.php';

try {
    $pdo = Database::connect($config['db']);
} catch (Throwable $e) {
    http_response_code(500);
    exit('<h1>Erreur de connexion</h1><p>Vérifiez config/config.php et la base MariaDB.</p>');
}

Auth::requireLogin();
$user = Auth::currentUser();

try {
    $repo = new DashboardRepository($pdo);
    $dashboard = $repo->getDefaultDashboard();
} catch (Throwable $e) {
    http_response_code(500);
    exit('<h1>Erreur dashboard</h1>');
}

if (!$dashboard) {
    $dashboard = [
        'name' => 'Dashboard coop (démo)',
        'widgets' => [
            [
                'title' => 'Équipements',
                'widget_type' => 'status_list',
                'data_json' => json_encode([
                    ['label' => 'Serveur fichiers', 'status' => 'online'],
                    ['label' => 'NAS sauvegarde', 'status' => 'online'],
                    ['label' => 'Poste atelier A', 'status' => 'offline'],
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'title' => 'Ressources',
                'widget_type' => 'metric_bars',
                'data_json' => json_encode([
                    ['label' => 'CPU', 'value' => 54],
                    ['label' => 'RAM', 'value' => 67],
                    ['label' => 'Disque', 'value' => 72],
                    ['label' => 'Réseau', 'value' => 39],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ],
    ];
}

function safe(array $widgetData): array
{
    return is_array($widgetData) ? $widgetData : [];
}

$isAdmin = in_array($user['role'], ['admin', 'super_admin'], true);
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($dashboard['name']) ?></title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header>
  <h1><?= htmlspecialchars($dashboard['name']) ?></h1>
  <div>
    <span>Connecté: <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
    <?php if ($isAdmin): ?>
      | <a class="admin-link" href="/admin/index.php">Administration</a>
    <?php endif; ?>
    | <a class="admin-link" href="/admin/logout.php">Déconnexion</a>
  </div>
</header>
<main class="grid">
  <?php foreach ($dashboard['widgets'] as $widget): ?>
    <?php $data = safe(json_decode($widget['data_json'], true) ?: []); ?>
    <section class="card">
      <h2><?= htmlspecialchars($widget['title']) ?></h2>
      <?php if ($widget['widget_type'] === 'status_list'): ?>
        <ul>
          <?php foreach ($data as $item): ?>
            <li>
              <?= htmlspecialchars($item['label'] ?? 'Élément') ?>:
              <span class="status <?= ($item['status'] ?? '') === 'online' ? 'ok' : 'down' ?>">
                <?= ($item['status'] ?? '') === 'online' ? 'en ligne' : 'hors ligne' ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php elseif ($widget['widget_type'] === 'metric_bars'): ?>
        <?php foreach ($data as $item): ?>
          <?php $value = (int)($item['value'] ?? 0); ?>
          <div class="metric-row">
            <div><?= htmlspecialchars($item['label'] ?? 'Métrique') ?> (<?= $value ?>%)</div>
            <div class="bar"><span style="width: <?= max(0, min(100, $value)) ?>%"></span></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</main>
</body>
</html>
