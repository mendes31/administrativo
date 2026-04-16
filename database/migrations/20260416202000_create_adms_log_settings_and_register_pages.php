<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsLogSettingsAndRegisterPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_settings')) {
            $this->table('adms_log_settings', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('session_debug_logs', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->create();
        }

        $this->execute(
            "INSERT INTO adms_log_settings (id, session_debug_logs, created_at, updated_at)
             VALUES (1, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()"
        );

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ListLogAcessos' LIMIT 1");
        if (!$ref) {
            return;
        }

        $groupId = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($groupId <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->ensurePage(
            'Configurações de Logs',
            'LogSettings',
            'log-settings',
            'logs',
            'Página para configurar logs de diagnóstico do sistema.',
            $groupId,
            $now
        );

        $this->ensurePage(
            'Salvar Configurações de Logs',
            'SaveLogSettings',
            'save-log-settings',
            'logs',
            'Endpoint para salvar configurações de logs de diagnóstico.',
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
        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' OR controller_url = '{$controllerUrl}' LIMIT 1");
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
        $newId = (int)($newRow['id'] ?? 0);
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
            foreach (['log-settings', 'save-log-settings'] as $slug) {
                $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = '{$slug}' LIMIT 1");
                if (!$row) {
                    continue;
                }
                $pid = (int)$row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('adms_log_settings')) {
            $this->table('adms_log_settings')->drop()->save();
        }
    }
}

