<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ACL para geração em massa de avaliações a partir do ciclo (Expand Fase 5).
 */
final class RegisterBulkCreatePerformanceReviews extends AbstractMigration
{
    public function up(): void
    {
        $this->registerPage();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'BulkCreatePerformanceReviews' LIMIT 1");
        if ($row) {
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }
        $this->bumpMenuCache();
    }

    private function registerPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupId = 36;
        foreach (['ListPerformanceReviews', 'ListPerformanceCycles', 'ListPerformanceGoals'] as $ref) {
            $group = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = '{$ref}' LIMIT 1");
            if ($group && !empty($group['adms_groups_page_id'])) {
                $groupId = (int) $group['adms_groups_page_id'];
                break;
            }
        }

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');
        $controller = 'BulkCreatePerformanceReviews';
        $existing = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
        if ($existing) {
            $this->grant((int) $existing['id']);
            return;
        }

        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Gerar Avaliações em Massa') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('bulk-create-performance-reviews') . ', '
            . $conn->quote('performance') . ', '
            . $conn->quote('Gera avaliações draft a partir do ciclo.') . ', '
            . '0, 0, 1, 1, ' . $groupId . ', '
            . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
        );

        $page = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
        if ($page) {
            $this->grant((int) $page['id']);
        }
    }

    private function grant(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $ref = null;
        foreach (['ListPerformanceReviews', 'ListPerformanceCycles', 'ListPerformanceGoals'] as $ctrl) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if ($ref) {
                break;
            }
        }
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }

    private function bumpMenuCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
