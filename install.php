<?php

$errors = [];
$success = '';
$configPath = __DIR__ . '/config/config.php';
$isInstalled = file_exists($configPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $dbName = trim($_POST['db_name'] ?? 'monitoringsi');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';

    $superUsername = trim($_POST['super_username'] ?? 'superadmin');
    $superPassword = $_POST['super_password'] ?? '';

    if ($dbUser === '' || $dbName === '') {
        $errors[] = 'Les paramètres base de données sont obligatoires.';
    }

    if (strlen($superPassword) < 10) {
        $errors[] = 'Le mot de passe super administrateur doit faire au moins 10 caractères.';
    }

    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $sql = file_get_contents(__DIR__ . '/install/sql/schema.sql');
            if ($sql === false) {
                throw new RuntimeException('Impossible de lire install/sql/schema.sql');
            }

            foreach (explode(';', $sql) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password_hash, role, created_at, updated_at)
                 VALUES (:username, :password_hash, 'super_admin', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'super_admin', updated_at = NOW()"
            );
            $stmt->execute([
                'username' => $superUsername,
                'password_hash' => password_hash($superPassword, PASSWORD_DEFAULT),
            ]);

            $configContent = "<?php\n\nreturn [\n"
                . "    'db' => [\n"
                . "        'host' => '" . addslashes($dbHost) . "',\n"
                . "        'port' => " . $dbPort . ",\n"
                . "        'database' => '" . addslashes($dbName) . "',\n"
                . "        'username' => '" . addslashes($dbUser) . "',\n"
                . "        'password' => '" . addslashes($dbPass) . "',\n"
                . "        'charset' => 'utf8mb4',\n"
                . "    ],\n"
                . "    'app' => [\n"
                . "        'name' => 'MonitoringSI',\n"
                . "        'base_url' => '',\n"
                . "    ],\n"
                . "    'uploads' => [\n"
                . "        'absolute_path' => __DIR__ . '/../storage/uploads/assets',\n"
                . "        'public_path' => '/storage/uploads/assets',\n"
                . "        'max_size_bytes' => 5 * 1024 * 1024,\n"
                . "    ],\n"
                . "];\n";

            if (!is_dir(__DIR__ . '/config')) {
                mkdir(__DIR__ . '/config', 0775, true);
            }

            if (file_put_contents($configPath, $configContent) === false) {
                throw new RuntimeException('Impossible d\'écrire config/config.php. Vérifiez les permissions.');
            }

            if (!is_dir(__DIR__ . '/storage/uploads/assets')) {
                mkdir(__DIR__ . '/storage/uploads/assets', 0775, true);
            }

            $success = 'Installation terminée. Connectez-vous sur /admin/login.php puis supprimez install.php.';
        } catch (Throwable $e) {
            $errors[] = 'Erreur installation: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installation MonitoringSI</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; max-width: 760px; }
    .box { border: 1px solid #ccc; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    label { display: block; margin: 8px 0 4px; }
    input { width: 100%; padding: 8px; }
    .error { color: #b91c1c; }
    .ok { color: #166534; }
  </style>
</head>
<body>
  <h1>Installateur MonitoringSI</h1>

  <?php if ($isInstalled): ?>
    <p class="ok">Configuration existante détectée (`config/config.php`). Vous pouvez relancer l'installation pour mettre à jour les paramètres.</p>
  <?php endif; ?>

  <?php foreach ($errors as $error): ?>
    <p class="error">• <?= htmlspecialchars($error) ?></p>
  <?php endforeach; ?>

  <?php if ($success): ?>
    <p class="ok"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>

  <form method="post">
    <div class="box">
      <h2>Base de données MariaDB</h2>
      <label>Hôte</label>
      <input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
      <label>Port</label>
      <input name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
      <label>Nom de la base</label>
      <input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'monitoringsi') ?>" required>
      <label>Utilisateur DB</label>
      <input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
      <label>Mot de passe DB</label>
      <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
    </div>

    <div class="box">
      <h2>Compte super administrateur</h2>
      <label>Nom utilisateur</label>
      <input name="super_username" value="<?= htmlspecialchars($_POST['super_username'] ?? 'superadmin') ?>" required>
      <label>Mot de passe (min. 10 caractères)</label>
      <input type="password" name="super_password" required>
    </div>

    <button type="submit">Lancer l'installation complète</button>
  </form>

  <p>Après installation, connectez-vous via <code>/admin/login.php</code>.</p>
</body>
</html>
