<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Garante linhas de ACL para páginas de simulação de custo.
 * Apenas Super Administrador (id = 1) recebe permission = 1.
 * Demais níveis ficam com 0 (liberação manual na matriz).
 */
final class GrantInvCostSimulationPermissions extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages') || !$this->hasTable('adms_access_levels')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $targets = $this->fetchAll(
            "SELECT id FROM adms_pages WHERE controller IN ('SaveInventoryCostSimulation', 'ExportInventoryCostSimulationPdf')"
        );
        foreach ($targets as $target) {
            $newId = (int)$target['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels al
                 ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)"
            );
            $this->execute(
                "UPDATE adms_access_levels_pages
                 SET permission = 1, updated_at = '{$now}'
                 WHERE adms_page_id = {$newId} AND adms_access_level_id = 1"
            );
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $rows = $this->fetchAll(
            "SELECT id FROM adms_pages WHERE controller IN ('SaveInventoryCostSimulation', 'ExportInventoryCostSimulationPdf')"
        );
        foreach ($rows as $row) {
            $pid = (int)$row['id'];
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
    }
}
