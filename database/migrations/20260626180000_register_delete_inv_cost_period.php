<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

final class RegisterDeleteInvCostPeriod extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DeleteInvCostPeriod' LIMIT 1");
        if ($exists) {
            return;
        }

        $group = $this->fetchRow('SELECT id FROM adms_groups_pages WHERE id = 33 LIMIT 1');
        $groupId = $group ? (int)$group['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            [
                'name' => 'Excluir Período de Custeio',
                'controller' => 'DeleteInvCostPeriod',
                'controller_url' => 'delete-inventory-cost-period',
                'directory' => 'inventory',
                'obs' => 'Exclusão de período em rascunho e dados vinculados (DRE, rateio).',
                'page_status' => 1,
                'public_page' => 0,
                'default_page' => 0,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->save();

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $source = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'CreateInvCostPeriod' LIMIT 1");
        if (!$source) {
            $source = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListInvCostPeriods' LIMIT 1");
        }
        if (!$source) {
            return;
        }

        $sourceId = (int)$source['id'];
        $target = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DeleteInvCostPeriod' LIMIT 1");
        if (!$target) {
            return;
        }
        $targetId = (int)$target['id'];

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$targetId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$sourceId}"
        );

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_access_levels_pages') && $this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DeleteInvCostPeriod' LIMIT 1");
            if ($row) {
                $pid = (int)$row['id'];
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
        }
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller = 'DeleteInvCostPeriod'");
        }
    }
}
