<?php

class DashboardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getDefaultDashboard(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM dashboards WHERE is_default = 1 ORDER BY id LIMIT 1');
        $dashboard = $stmt->fetch();
        if (!$dashboard) {
            return null;
        }

        $dashboard['widgets'] = $this->getWidgets((int)$dashboard['id']);
        return $dashboard;
    }

    public function getWidgets(int $dashboardId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM widgets WHERE dashboard_id = :dashboard_id ORDER BY position ASC, id ASC');
        $stmt->execute(['dashboard_id' => $dashboardId]);
        return $stmt->fetchAll();
    }

    public function listDashboards(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM dashboards ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function saveDashboard(string $name, array $widgets): int
    {
        $dashboardId = (int)$this->pdo->query('SELECT id FROM dashboards WHERE is_default = 1 ORDER BY id LIMIT 1')->fetchColumn();

        if (!$dashboardId) {
            $stmt = $this->pdo->prepare('INSERT INTO dashboards (name, slug, is_default, created_at, updated_at) VALUES (:name, :slug, 1, NOW(), NOW())');
            $stmt->execute([
                'name' => $name,
                'slug' => 'default',
            ]);
            $dashboardId = (int)$this->pdo->lastInsertId();
        } else {
            $stmt = $this->pdo->prepare('UPDATE dashboards SET name = :name, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['name' => $name, 'id' => $dashboardId]);
        }

        $this->pdo->prepare('DELETE FROM widgets WHERE dashboard_id = :dashboard_id')->execute(['dashboard_id' => $dashboardId]);

        $insert = $this->pdo->prepare('INSERT INTO widgets (dashboard_id, title, widget_type, data_json, position, created_at, updated_at) VALUES (:dashboard_id, :title, :widget_type, :data_json, :position, NOW(), NOW())');

        foreach ($widgets as $index => $widget) {
            $insert->execute([
                'dashboard_id' => $dashboardId,
                'title' => $widget['title'] ?? 'Widget',
                'widget_type' => $widget['widget_type'] ?? 'custom',
                'data_json' => json_encode($widget['data'] ?? [], JSON_UNESCAPED_UNICODE),
                'position' => $index,
            ]);
        }

        return $dashboardId;
    }
}
