<?php
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = Database::connect($config['db']);
Auth::requireLogin();
Auth::requireRole(['super_admin']);

$token = bin2hex(random_bytes(16));
$_SESSION['csrf'] = $token;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['csrf'] ?? '') === ($_SESSION['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username !== '' && $password !== '') {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role, created_at, updated_at) VALUES (:username, :password_hash, :role, NOW(), NOW())');
            $stmt->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
            ]);
            $message = 'Administrateur créé';
            Auth::log($pdo, Auth::currentUser()['id'], 'admin_created', 'Création admin ' . $username);
        }
    }

    if ($action === 'save_permissions') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $selected = $_POST['modules'] ?? [];

        $pdo->prepare('DELETE FROM module_permissions WHERE user_id = :user_id')->execute(['user_id' => $userId]);

        $modules = $pdo->query('SELECT id, code FROM modules')->fetchAll();
        $insert = $pdo->prepare('INSERT INTO module_permissions (user_id, module_id, can_manage, created_at, updated_at) VALUES (:user_id, :module_id, :can_manage, NOW(), NOW())');

        foreach ($modules as $module) {
            $canManage = in_array($module['code'], $selected, true) ? 1 : 0;
            if ($canManage) {
                $insert->execute([
                    'user_id' => $userId,
                    'module_id' => $module['id'],
                    'can_manage' => 1,
                ]);
            }
        }

        $message = 'Permissions mises à jour';
        Auth::log($pdo, Auth::currentUser()['id'], 'permissions_updated', 'Mise à jour permissions user #' . $userId);
    }
}

$admins = $pdo->query("SELECT id, username, role FROM users WHERE role IN ('admin','super_admin') ORDER BY role DESC, username ASC")->fetchAll();
$modules = $pdo->query('SELECT id, code, label FROM modules ORDER BY id ASC')->fetchAll();

$permissionMap = [];
$stmt = $pdo->query('SELECT user_id, module_id, can_manage FROM module_permissions');
foreach ($stmt->fetchAll() as $row) {
    $permissionMap[(int)$row['user_id']][(int)$row['module_id']] = (int)$row['can_manage'] === 1;
}
?><!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Gestion utilisateurs</title>
<link rel="stylesheet" href="../public/assets/css/app.css"></head>
<body>
<header>
  <h1>Super administration - utilisateurs & droits modules</h1>
  <a class="admin-link" href="index.php">Retour admin</a>
</header>
<main class="grid">
  <section class="card">
    <h2>Créer un administrateur</h2>
    <?php if ($message): ?><p><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
      <input type="hidden" name="action" value="create_admin">
      <label>Nom utilisateur</label>
      <input name="username" required>
      <label>Mot de passe</label>
      <input name="password" type="password" required>
      <button type="submit">Créer admin</button>
    </form>
  </section>

  <section class="card">
    <h2>Droits par module</h2>
    <?php foreach ($admins as $admin): ?>
      <?php if ($admin['role'] === 'super_admin') continue; ?>
      <form method="post" style="margin-bottom: 12px; border: 1px solid #334155; padding: 8px;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="action" value="save_permissions">
        <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
        <strong><?= htmlspecialchars($admin['username']) ?></strong><br>
        <?php foreach ($modules as $module): ?>
          <?php $checked = !empty($permissionMap[(int)$admin['id']][(int)$module['id']]); ?>
          <label>
            <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($module['code']) ?>" <?= $checked ? 'checked' : '' ?>>
            <?= htmlspecialchars($module['label']) ?> (<?= htmlspecialchars($module['code']) ?>)
          </label><br>
        <?php endforeach; ?>
        <button type="submit">Enregistrer droits</button>
      </form>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>
