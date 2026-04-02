<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Diretório de membros na timeline; permissões espelham o feed (Timeline).
 */
final class RegisterTimelineMembersPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineMembers' LIMIT 1");
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
            'name' => 'Membros (Timeline)',
            'controller' => 'TimelineMembers',
            'controller_url' => 'timeline-members',
            'directory' => 'timeline',
            'obs' => 'Listagem de colaboradores com link ao perfil na timeline.',
            'public_page' => 0,
            'default_page' => 1,
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

        $timeline = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'Timeline' LIMIT 1");
        if (!$timeline) {
            return;
        }
        $tid = (int)$timeline['id'];

        // Mesmos níveis de acesso que já têm o feed da timeline.
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$tid}"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineMembers' LIMIT 1");
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
