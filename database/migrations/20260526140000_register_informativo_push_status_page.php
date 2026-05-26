<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registra a página InformativoPushStatus (relatório de entrega push por usuário).
 */
final class RegisterInformativoPushStatusPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'InformativoPushStatus' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'Status Push — Informativo',
            'controller' => 'InformativoPushStatus',
            'controller_url' => 'informativo-push-status',
            'directory' => 'informativos',
            'obs' => 'Exibe relatório de entrega push PWA por usuário e dispositivo para um informativo.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => 30,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ResendInformativoPush' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'ViewInformativo' LIMIT 1"
            );
        }
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refId}"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $row = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'InformativoPushStatus' LIMIT 1"
        );
        if (!$row) {
            return;
        }

        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
    }
}
