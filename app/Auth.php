<?php

class Auth
{
    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::log($pdo, null, 'login_failed', 'Échec connexion pour ' . $username);
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];

        self::log($pdo, (int)$user['id'], 'login_success', 'Connexion réussie');
        return true;
    }

    public static function logout(PDO $pdo): void
    {
        $user = self::currentUser();
        if ($user) {
            self::log($pdo, (int)$user['id'], 'logout', 'Déconnexion');
        }

        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::currentUser()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function requireRole(array $roles): void
    {
        $user = self::currentUser();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            exit('Accès refusé');
        }
    }

    public static function canAccessModule(PDO $pdo, string $moduleCode): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        if ($user['role'] === 'super_admin') {
            return true;
        }

        if ($user['role'] !== 'admin') {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT mp.can_manage
             FROM module_permissions mp
             INNER JOIN modules m ON m.id = mp.module_id
             WHERE mp.user_id = :user_id AND m.code = :code
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => (int)$user['id'],
            'code' => $moduleCode,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public static function requireModule(PDO $pdo, string $moduleCode): void
    {
        if (!self::canAccessModule($pdo, $moduleCode)) {
            http_response_code(403);
            exit('Module non autorisé pour ce compte administrateur');
        }
    }

    public static function log(PDO $pdo, ?int $userId, string $action, string $details): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO auth_logs (user_id, action, details, created_at) VALUES (:user_id, :action, :details, NOW())');
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
            ]);
        } catch (Throwable $e) {
            // no-op
        }
    }
}
