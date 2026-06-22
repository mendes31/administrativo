<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstEquipamentoQrPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $pages = [
            [
                'name' => 'Ler QR equipamento SST',
                'controller' => 'SstScanEquipamento',
                'controller_url' => 'sst-scan-equipamento',
                'obs' => 'Entrada via QR Code: abre checklist da vistoria pendente.',
                'ref' => 'SstExecuteEquipamentoVistoria',
            ],
            [
                'name' => 'Exportar QR equipamento SST',
                'controller' => 'SstExportEquipamentoQr',
                'controller_url' => 'sst-export-equipamento-qr',
                'obs' => 'Imagem PNG do QR Code do equipamento para etiqueta.',
                'ref' => 'SstViewEquipamento',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($pages as $pageDef) {
            $exists = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['controller']) . "' LIMIT 1"
            );
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $pageDef['name'],
                'controller' => $pageDef['controller'],
                'controller_url' => $pageDef['controller_url'],
                'directory' => 'sst',
                'obs' => $pageDef['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => 41,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            if (!$this->hasTable('adms_access_levels_pages')) {
                continue;
            }

            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['controller']) . "' LIMIT 1"
            );
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($pageDef['ref']) . "' LIMIT 1"
            );
            if (!$page || !$ref) {
                continue;
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
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN ('SstScanEquipamento', 'SstExportEquipamentoQr')");
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
