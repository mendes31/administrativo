<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configuração do Canal de Denúncias no banco (token cron + chave criptografia).
 */
final class WhistleblowingConfigPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_config')) {
            $this->table('adms_whistleblowing_config', ['id' => 'id', 'primary_key' => ['id']])
                ->addColumn('http_cron_token', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('encryption_key', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Chave AES-256; não reexibida após salvar'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];
        $now = date('Y-m-d H:i:s');

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'WhistleblowingConfig' LIMIT 1");
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => 'Configuração Canal de Denúncias',
            'controller' => 'WhistleblowingConfig',
            'controller_url' => 'whistleblowing-config',
            'directory' => 'whistleblowing',
            'obs' => 'Token do cron LGPD e chave de criptografia dos relatos (sem .env).',
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
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'WhistleblowingConfig' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        if ($this->hasTable('adms_whistleblowing_config')) {
            $this->table('adms_whistleblowing_config')->drop()->save();
        }
    }
}
