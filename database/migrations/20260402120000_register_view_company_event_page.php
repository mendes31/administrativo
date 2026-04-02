<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterViewCompanyEventPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }
        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'view-company-event' LIMIT 1");
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
            'name' => 'Visualizar Evento Corporativo',
            'controller' => 'ViewCompanyEvent',
            'controller_url' => 'view-company-event',
            'directory' => 'companyEvents',
            'obs' => 'Visualização de evento corporativo (somente leitura) e confirmação de presença.',
            'public_page' => 0,
            'default_page' => 1,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller_url = 'view-company-event' LIMIT 1");
        }
    }
}
