<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSacRateTicketPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'SacRateTicket' LIMIT 1");
        if ($exists) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'SAC' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'Avaliar Chamado SAC',
            'controller' => 'SacRateTicket',
            'controller_url' => 'sac-rate-ticket',
            'directory' => 'sac',
            'obs' => 'Módulo SAC (SmartSAC).',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM adms_pages WHERE controller = 'SacRateTicket'");
    }
}
