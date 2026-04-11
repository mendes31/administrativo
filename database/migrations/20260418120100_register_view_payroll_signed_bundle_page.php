<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Download do PDF unificado (original + trilha de ciência) — titular e RH com permissões alinhadas ao comprovante/importação.
 */
final class RegisterViewPayrollSignedBundlePage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'PayrollSignatureReceipt' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
        }
        if (!$ref) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ViewPayrollSignedBundle' LIMIT 1");
        if ($exists) {
            return;
        }

        $gid = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => 'PDF assinado com trilha (folha RH)',
            'controller' => 'ViewPayrollSignedBundle',
            'controller_url' => 'view-payroll-signed-bundle',
            'directory' => 'portal',
            'obs' => 'Documento original + página(s) de trilha de confirmação; o ficheiro em storage_path permanece o original.',
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
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $refId = (int)$ref['id'];
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
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ViewPayrollSignedBundle' LIMIT 1");
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
