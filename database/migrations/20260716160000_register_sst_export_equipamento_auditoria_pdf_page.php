<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstExportEquipamentoAuditoriaPdfPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'SstExportEquipamentoAuditoriaPdf' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'Segurança e Medicina' LIMIT 1"
        );
        $groupId = $group ? (int) $group['id'] : 42;
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'PDF auditoria equipamentos SST',
            'controller' => 'SstExportEquipamentoAuditoriaPdf',
            'controller_url' => 'sst-export-equipamento-auditoria-pdf',
            'directory' => 'sst',
            'obs' => 'Relatório profissional de vistorias e recargas por período (cabeçalho institucional).',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $groupId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        if (!$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuPermissionCache();
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'SstExportEquipamentoAuditoriaPdf' LIMIT 1"
        );
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'SstViewEquipamento' LIMIT 1"
        );
        if (!$page || !$ref) {
            $this->bumpMenuPermissionCache();
            return;
        }

        $pageId = (int) $page['id'];
        $refPageId = (int) $ref['id'];

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refPageId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller = 'SstExportEquipamentoAuditoriaPdf'");
        }
        $this->bumpMenuPermissionCache();
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
