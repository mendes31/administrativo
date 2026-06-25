<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Permissão para cancelar/excluir treinamentos realizados na matriz.
 */
final class RegisterDeleteCompletedTrainingPage extends AbstractMigration
{
    private const CONTROLLER = 'DeleteCompletedTraining';
    private const REF_CONTROLLER = 'EditCompletedTraining';

    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes(self::CONTROLLER) . "' LIMIT 1"
        );

        if (!$exists) {
            $this->table('adms_pages')->insert([
                'name' => 'Excluir Treinamento Realizado',
                'controller' => self::CONTROLLER,
                'controller_url' => 'delete-completed-training',
                'directory' => 'trainings',
                'obs' => 'Permissão para cancelar treinamentos realizados na matriz e devolvê-los ao status pendente.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => 24,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        $this->syncPermissionsFromReference();
        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "DELETE FROM adms_pages WHERE controller = '" . addslashes(self::CONTROLLER) . "'"
        );
        $this->bumpMenuPermissionCache();
    }

    private function syncPermissionsFromReference(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes(self::CONTROLLER) . "' LIMIT 1"
        );
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes(self::REF_CONTROLLER) . "' LIMIT 1"
        );

        if (!$page || !$ref) {
            return;
        }

        $pageId = (int)$page['id'];
        $refPageId = (int)$ref['id'];
        $now = date('Y-m-d H:i:s');

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

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string)time());
    }
}
