<?php
require_once __DIR__ . '/../app/bootstrap.php';

$error = '';

try {
    $pdo = Database::connect($config['db']);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erreur DB: vérifiez la configuration.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::login($pdo, $username, $password)) {
        header('Location: /public/index.php');
        exit;
    }

    $error = 'Identifiants invalides';
}
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Connexion</title></head>
<body>
  <h1>Connexion MonitoringSI</h1>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Utilisateur</label><input name="username" required>
    <label>Mot de passe</label><input name="password" type="password" required>
    <button type="submit">Se connecter</button>
  </form>
</body></html>
