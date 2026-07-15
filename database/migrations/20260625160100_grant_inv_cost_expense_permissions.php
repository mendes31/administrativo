<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * ACL DRE/rateio/período: só Super Admin (id=1) liberado por padrão.
 */
final class GrantInvCostExpensePermissions extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages') || !$this->hasTable('adms_access_levels')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $targets = $this->fetchAll(
            "SELECT id FROM adms_pages WHERE controller IN (
                'ViewInvCostPeriod',
                'UpdateInvCostPeriod',
                'ImportInvCostDre',
                'SaveInvCostAllocationRules'
            )"
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
            "SELECT id FROM adms_pages WHERE controller IN (
                'ViewInvCostPeriod',
                'UpdateInvCostPeriod',
                'ImportInvCostDre',
                'SaveInvCostAllocationRules'
            )"
        );
        foreach ($rows as $row) {
            $pid = (int)$row['id'];
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
    }
}
