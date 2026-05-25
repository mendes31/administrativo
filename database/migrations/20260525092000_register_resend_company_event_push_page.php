<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterResendCompanyEventPushPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ResendCompanyEventPush' LIMIT 1");
        if ($exists) {
            return;
        }

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ViewCompanyEvent' LIMIT 1");
        if (!$ref) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $gid = (int) ($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => 'Reenviar push — Evento Corporativo',
            'controller' => 'ResendCompanyEventPush',
            'controller_url' => 'resend-company-event-push',
            'directory' => 'companyEvents',
            'obs' => 'Reenvio manual de push PWA para evento corporativo (pendentes ou todos).',
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
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ResendCompanyEventPush' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }
    }
}
