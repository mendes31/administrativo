<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsNotificationSettings extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_notification_settings')) {
            $this->table('adms_notification_settings', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
                ->addColumn('setting_key', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('enabled', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['setting_key'], ['unique' => true, 'name' => 'uk_notification_setting_key'])
                ->create();
        }

        $defaults = [
            'sst_pendencias_email',
            'sst_pendencias_inapp',
            'sst_pendencias_incluir_treinamentos',
            'training_pending',
            'training_expiring',
            'training_expired',
            'training_new_mandatory',
        ];

        foreach ($defaults as $key) {
            $keySql = str_replace("'", "''", $key);
            $exists = $this->fetchRow(
                "SELECT id FROM adms_notification_settings WHERE setting_key = '{$keySql}' LIMIT 1"
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_notification_settings')->insert([
                'setting_key' => $key,
                'enabled' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ])->save();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'EmailConfig' LIMIT 1"
        );
        $groupId = (int) ($ref['adms_groups_page_id'] ?? 26);
        $now = date('Y-m-d H:i:s');

        $this->ensurePage(
            'Configurações de Notificações',
            'NotificationSettings',
            'notification-settings',
            'settings',
            'Ativar ou desativar notificações automáticas do sistema (e-mail e in-app).',
            $groupId,
            $now
        );

        $this->ensurePage(
            'Salvar Configurações de Notificações',
            'SaveNotificationSettings',
            'save-notification-settings',
            'settings',
            'Endpoint para salvar configurações de notificações automáticas.',
            $groupId,
            $now
        );
    }

    private function ensurePage(
        string $name,
        string $controller,
        string $controllerUrl,
        string $directory,
        string $obs,
        int $groupId,
        string $now
    ): void {
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '{$controller}' OR controller_url = '{$controllerUrl}' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => $name,
            'controller' => $controller,
            'controller_url' => $controllerUrl,
            'directory' => $directory,
            'obs' => $obs,
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $groupId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            foreach (['notification-settings', 'save-notification-settings'] as $slug) {
                $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = '{$slug}' LIMIT 1");
                if (!$row) {
                    continue;
                }
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('adms_notification_settings')) {
            $this->table('adms_notification_settings')->drop()->save();
        }
    }
}
