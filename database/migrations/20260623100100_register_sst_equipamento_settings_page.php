<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstEquipamentoSettingsPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstEquipamentoSettings' LIMIT 1");
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Configurações vistorias equipamentos SST',
            'controller' => 'SstEquipamentoSettings',
            'controller_url' => 'sst-equipamento-settings',
            'directory' => 'sst',
            'obs' => 'Periodicidade, dias de geração e tolerância de vistorias de equipamentos.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => 41,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        if (!$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuPermissionCache();

            return;
        }

        $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstEquipamentoSettings' LIMIT 1");
        if (!$page) {
            return;
        }
        $pageId = (int) $page['id'];
        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstListEquipamentos' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstListInspecoes' LIMIT 1");
        }
        if (!$ref) {
            $this->bumpMenuPermissionCache();

            return;
        }
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
            $this->execute("DELETE FROM adms_pages WHERE controller = 'SstEquipamentoSettings'");
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
