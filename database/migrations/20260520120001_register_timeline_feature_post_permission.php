<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Permissão de botão para marcar publicação em destaque na Timeline.
 */
final class RegisterTimelineFeaturePostPermission extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineFeaturePost' LIMIT 1");
        if ($exists) {
            return;
        }

        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Comunicação Social' LIMIT 1");
        if (!$g) {
            return;
        }
        $gid = (int)$g['id'];
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'Destaque na Timeline',
            'controller' => 'TimelineFeaturePost',
            'controller_url' => 'timeline-feature-post',
            'directory' => 'timeline',
            'obs' => 'Permite marcar publicação como em destaque (topo do dia).',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int)($newRow['id'] ?? 0);
        if ($newId <= 0) {
            return;
        }

        // Mesmos níveis que moderação da timeline (ajustável depois em Níveis de Acesso).
        $moderate = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineModerate' LIMIT 1");
        if (!$moderate) {
            return;
        }
        $mid = (int)$moderate['id'];

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$mid}"
        );

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineFeaturePost' LIMIT 1");
        if (!$row) {
            return;
        }
        $pid = (int)$row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
    }
}
