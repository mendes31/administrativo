<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Garante grupo Ajuda e página ContextHelp como default_page (correção pós-registro inicial). */
final class EnsureAjudaGroupContextHelp extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $groupId = $this->ensureAjudaGroupId($now);

        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ContextHelp' LIMIT 1");
        if ($row) {
            $pageId = (int) ($row['id'] ?? 0);
            $this->execute(
                "UPDATE adms_pages SET
                    adms_groups_page_id = {$groupId},
                    default_page = 1,
                    obs = 'Manual do sistema com ajuda contextual por tela (tecla F1). Página padrão — não aparece no menu lateral.',
                    updated_at = '{$now}'
                 WHERE id = {$pageId} LIMIT 1"
            );

            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 1, al.id, {$pageId}, '{$now}', '{$now}'
                     FROM adms_access_levels al
                     ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
                );
            }
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $dashboard = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'Dashboard' LIMIT 1");
        $gid = (int) ($dashboard['adms_groups_page_id'] ?? 1);
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "UPDATE adms_pages SET adms_groups_page_id = {$gid}, default_page = 0, updated_at = '{$now}'
             WHERE controller = 'ContextHelp' LIMIT 1"
        );

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
