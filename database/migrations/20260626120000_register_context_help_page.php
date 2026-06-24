<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Ajuda de contexto (F1) — manual do sistema (página padrão, grupo Ajuda, sem item de menu). */
final class RegisterContextHelpPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $groupId = $this->ensureAjudaGroupId($now);

        $existing = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ContextHelp' LIMIT 1");
        if ($existing) {
            $pageId = (int) ($existing['id'] ?? 0);
            $this->execute(
                "UPDATE adms_pages SET
                    name = 'Ajuda de contexto (F1)',
                    controller_url = 'context-help',
                    directory = 'help',
                    obs = 'Manual do sistema com ajuda contextual por tela (tecla F1). Página padrão — não aparece no menu lateral.',
                    public_page = 0,
                    default_page = 1,
                    page_status = 1,
                    adms_groups_page_id = {$groupId},
                    updated_at = '{$now}'
                 WHERE id = {$pageId} LIMIT 1"
            );
        } else {
            $this->table('adms_pages')->insert([
                'name' => 'Ajuda de contexto (F1)',
                'controller' => 'ContextHelp',
                'controller_url' => 'context-help',
                'directory' => 'help',
                'obs' => 'Manual do sistema com ajuda contextual por tela (tecla F1). Página padrão — não aparece no menu lateral.',
                'public_page' => 0,
                'default_page' => 1,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $pageId = (int) ($newRow['id'] ?? 0);
        }

        if ($pageId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuPermissionCache();
            return;
        }

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ContextHelp' LIMIT 1");
        if (!$row) {
            return;
        }
        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        $this->bumpMenuPermissionCache();
    }

    private function ensureAjudaGroupId(string $now): int
    {
        if (!$this->hasTable('adms_groups_pages')) {
            return 1;
        }

        $row = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Ajuda' LIMIT 1");
        if ($row) {
            return (int) ($row['id'] ?? 1);
        }

        $this->table('adms_groups_pages')->insert([
            'name' => 'Ajuda',
            'obs' => 'Manual e ajuda contextual do sistema (F1).',
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $new = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');

        return (int) ($new['id'] ?? 1);
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
