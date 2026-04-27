<?php

require_once __DIR__ . '/../app/bootstrap.php';

$pdo = Database::connect($config['db']);
Auth::requireLogin();
Auth::requireRole(['admin', 'super_admin']);
Auth::requireModule($pdo, 'dashboard_editor');

$repo = new DashboardRepository($pdo);
$dashboard = $repo->getDefaultDashboard();
$widgets = $dashboard['widgets'] ?? [];
$token = bin2hex(random_bytes(16));
$_SESSION['csrf'] = $token;
$user = Auth::currentUser();
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin MonitoringSI</title>
  <link rel="stylesheet" href="../public/assets/css/app.css">
  <style>
    .toolbar, .dropzone { padding: 12px; }
    .tool { background: #334155; border: 1px dashed #94a3b8; border-radius: 6px; padding: 8px; margin-bottom: 8px; cursor: grab; }
    .widget { border: 1px solid #475569; border-radius: 6px; padding: 8px; margin-bottom: 8px; background: #0f172a; }
    input, textarea, select { width: 100%; margin: 4px 0 8px; }
    .layout { display:grid; grid-template-columns: 280px 1fr; gap:16px; padding:12px; }
    button { padding: 8px 12px; }
  </style>
</head>
<body>
<header>
  <h1>Administration Dashboard</h1>
  <div>
    <span><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</span> |
    <?php if ($user['role'] === 'super_admin'): ?>
      <a class="admin-link" href="users.php">Gestion utilisateurs</a> |
    <?php endif; ?>
    <a class="admin-link" href="../public/index.php">Voir dashboard</a> |
    <a class="admin-link" href="logout.php">Déconnexion</a>
  </div>
</header>

<div class="layout">
  <aside class="card toolbar">
    <h2>Modules (drag & drop)</h2>
    <div class="tool" draggable="true" data-widget-type="status_list">Liste de statuts</div>
    <div class="tool" draggable="true" data-widget-type="metric_bars">Barres de métriques</div>
    <div class="tool" draggable="true" data-widget-type="custom">Widget personnalisé</div>

    <?php if (Auth::canAccessModule($pdo, 'asset_manager')): ?>
      <h2>Assets</h2>
      <form action="upload_asset.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
        <input type="file" name="asset" required>
        <button type="submit">Uploader</button>
      </form>
    <?php endif; ?>
  </aside>

  <section class="card dropzone">
    <h2>Éditeur de dashboard</h2>
    <label>Nom dashboard</label>
    <input id="dashboardName" value="<?= htmlspecialchars($dashboard['name'] ?? 'Dashboard coop') ?>">

    <div id="widgetCanvas">
      <?php foreach ($widgets as $widget): ?>
        <div class="widget" draggable="true" data-widget-type="<?= htmlspecialchars($widget['widget_type']) ?>">
          <label>Titre</label>
          <input class="widget-title" value="<?= htmlspecialchars($widget['title']) ?>">
          <label>Type</label>
          <select class="widget-type">
            <option value="status_list" <?= $widget['widget_type'] === 'status_list' ? 'selected' : '' ?>>status_list</option>
            <option value="metric_bars" <?= $widget['widget_type'] === 'metric_bars' ? 'selected' : '' ?>>metric_bars</option>
            <option value="custom" <?= $widget['widget_type'] === 'custom' ? 'selected' : '' ?>>custom</option>
          </select>
          <label>Data JSON</label>
          <textarea class="widget-data" rows="4"><?= htmlspecialchars($widget['data_json']) ?></textarea>
          <button class="remove-widget" type="button">Supprimer</button>
        </div>
      <?php endforeach; ?>
    </div>

    <button id="saveBtn" type="button">Sauvegarder le dashboard</button>
    <p id="message"></p>
  </section>
</div>

<script>
  window.MONITORING_ADMIN = { csrf: '<?= htmlspecialchars($token) ?>' };
</script>
<script src="assets/js/admin.js"></script>
</body>
</html>
