<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Página Governança LGPD do Canal de Denúncias.
 */
final class WhistleblowingGovernancePage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];
        $now = date('Y-m-d H:i:s');

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'WhistleblowingGovernanceLgpd' LIMIT 1");
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => 'Governança LGPD — Canal de Denúncias',
            'controller' => 'WhistleblowingGovernanceLgpd',
            'controller_url' => 'whistleblowing-governance',
            'directory' => 'whistleblowing',
            'obs' => 'Política de retenção, histórico de execuções e disparo manual do cron LGPD.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        if ($this->hasTable('adms_access_levels_pages')) {
            $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $newId = (int) ($newRow['id'] ?? 0);
            if ($newId > 0) {
                $this->execute(
                    "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$newId}, '{$now}', '{$now}' FROM adms_access_levels al"
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'WhistleblowingGovernanceLgpd' LIMIT 1");
        if (!$row) {
            return;
        }

        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
    }
}
