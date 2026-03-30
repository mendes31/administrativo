<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante página de permissão "Ver comentários" e reverte desativação acidental.
 */
final class TimelineViewCommentsPageAndEnable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'TimelineViewComments' LIMIT 1");
        if ($exists) {
            $this->execute("UPDATE adms_pages SET page_status = 1, updated_at = NOW() WHERE controller = 'TimelineViewComments'");

            return;
        }

        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Comunicação Social' LIMIT 1");
        $groupId = $group ? (int)$group['id'] : 1;

        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            [
                'name' => 'Ver comentários Timeline',
                'controller' => 'TimelineViewComments',
                'controller_url' => 'timeline-view-comments',
                'directory' => 'timeline',
                'obs' => 'Ler comentários sem obrigar permissão de comentar.',
                'page_status' => 1,
                'public_page' => 0,
                'default_page' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->save();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute("UPDATE adms_pages SET page_status = 0 WHERE controller = 'TimelineViewComments'");
    }
}
