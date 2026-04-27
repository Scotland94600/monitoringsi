<?php
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = Database::connect($config['db']);
Auth::requireLogin();
Auth::requireRole(['admin', 'super_admin']);
Auth::requireModule($pdo, 'asset_manager');

if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(403);
    exit('CSRF invalide');
}

if (empty($_FILES['asset']) || $_FILES['asset']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php');
    exit;
}

$maxSize = (int)($config['uploads']['max_size_bytes'] ?? 0);
if ($_FILES['asset']['size'] > $maxSize) {
    exit('Fichier trop volumineux');
}

$uploadDir = $config['uploads']['absolute_path'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['asset']['name']));
$target = rtrim($uploadDir, '/') . '/' . time() . '_' . $filename;

if (!move_uploaded_file($_FILES['asset']['tmp_name'], $target)) {
    exit('Échec upload');
}

try {
    $stmt = $pdo->prepare('INSERT INTO assets (filename, path, mime_type, size, created_at) VALUES (:filename, :path, :mime, :size, NOW())');
    $stmt->execute([
        'filename' => $filename,
        'path' => $target,
        'mime' => $_FILES['asset']['type'] ?? 'application/octet-stream',
        'size' => (int)$_FILES['asset']['size'],
    ]);

    Auth::log($pdo, Auth::currentUser()['id'], 'asset_uploaded', 'Asset upload ' . $filename);
} catch (Throwable $e) {
    // ignore DB error, file still uploaded
}

header('Location: index.php');
