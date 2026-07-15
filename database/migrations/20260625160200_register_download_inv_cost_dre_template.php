<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

final class RegisterDownloadInvCostDreTemplate extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow('SELECT id FROM adms_groups_pages WHERE id = 33 LIMIT 1');
        $groupId = $group ? (int)$group['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DownloadInvCostDreTemplate' LIMIT 1");
        if (!$exists) {
            $this->table('adms_pages')->insert([
                [
                    'name' => 'Baixar Template DRE (Custeio)',
                    'controller' => 'DownloadInvCostDreTemplate',
                    'controller_url' => 'download-inventory-cost-dre-template',
                    'directory' => 'inventory',
                    'obs' => 'Download do CSV modelo para importação do DRE no período de custeio.',
                    'page_status' => 1,
                    'public_page' => 0,
                    'default_page' => 0,
                    'adms_packages_page_id' => 1,
                    'adms_groups_page_id' => $groupId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->save();
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $source = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportInvCostDre' LIMIT 1");
        if (!$source) {
            $source = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ViewInvCostPeriod' LIMIT 1");
        }
        if (!$source) {
            return;
        }

        $target = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DownloadInvCostDreTemplate' LIMIT 1");
        if (!$target) {
            return;
        }

        $sourceId = (int)$source['id'];
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
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DownloadInvCostDreTemplate' LIMIT 1");
            if ($row && $this->hasTable('adms_access_levels_pages')) {
                $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . (int)$row['id']);
            }
            $this->execute("DELETE FROM adms_pages WHERE controller = 'DownloadInvCostDreTemplate'");
        }
    }
}
