<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

$pdo = Database::connect($config['db']);
Auth::requireLogin();
Auth::requireRole(['admin', 'super_admin']);
Auth::requireModule($pdo, 'dashboard_editor');

$payload = json_decode(file_get_contents('php://input'), true);
if (($payload['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF invalide']);
    exit;
}

try {
    $repo = new DashboardRepository($pdo);
    $dashboardId = $repo->saveDashboard($payload['name'] ?? 'Dashboard coop', $payload['widgets'] ?? []);
    Auth::log($pdo, Auth::currentUser()['id'], 'dashboard_saved', 'Dashboard #' . $dashboardId . ' sauvegardé');
    echo json_encode(['ok' => true, 'dashboard_id' => $dashboardId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
