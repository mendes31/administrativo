<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Concede páginas de Treinamentos SST aos níveis que já têm Exames SST (ou Dashboard SST).
 */
final class SyncSstTreinamentosPagesPermissions extends AbstractMigration
{
    /** @var list<string> */
    private const NEW_CONTROLLERS = [
        'SstListTreinamentos',
        'SstCreateTreinamento',
        'SstViewTreinamento',
        'SstUpdateTreinamento',
        'SstDeleteTreinamento',
        'SstListTreinamentoNecessidade',
        'SstCreateTreinamentoNecessidade',
        'SstUpdateTreinamentoNecessidade',
        'SstDeleteTreinamentoNecessidade',
        'SstListRiscoTreinamento',
        'SstCreateRiscoTreinamento',
        'SstUpdateRiscoTreinamento',
        'SstDeleteRiscoTreinamento',
        'SstSaveRiscoTreinamentos',
        'SstListTreinamentoVinculos',
        'SstViewTreinamentoVinculo',
        'SstApplyTreinamento',
        'SstSyncTreinamentoVinculos',
        'SstReportTreinamentos',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstListExames' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SstDashboard' LIMIT 1");
        }
        if (!$ref) {
            return;
        }
        $refPageId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');

        foreach (self::NEW_CONTROLLERS as $controller) {
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];

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
        // Permissões não revertidas automaticamente.
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
