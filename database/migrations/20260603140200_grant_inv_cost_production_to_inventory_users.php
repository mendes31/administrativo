<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Garante acesso às telas de custeio para perfis que já listam itens de estoque.
 */
final class GrantInvCostProductionToInventoryUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $source = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListInventoryItems' LIMIT 1");
        if (!$source) {
            return;
        }
        $sourceId = (int)$source['id'];
        $now = date('Y-m-d H:i:s');

        $targets = $this->fetchAll(
            "SELECT id FROM adms_pages WHERE controller IN (
                'ListInvCostProductionBatches',
                'ListInvCostPeriods',
                'CreateInvCostPeriod'
            )"
        );
        foreach ($targets as $target) {
            $newId = (int)$target['id'];
            $this->execute(
                "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$sourceId}"
            );
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        // Sem rollback automático — permissões podem ter sido ajustadas manualmente.
    }
}
