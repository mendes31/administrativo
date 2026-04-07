<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Rotas: importação de PDF (RH), listagem e visualização segura para o colaborador.
 */
final class RegisterPayrollDocumentPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $refImport = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ImportUsers' LIMIT 1");
        $refPortal = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'EmployeePortal' LIMIT 1");

        $pages = [
            [
                'name' => 'Importar documentos de folha (PDF)',
                'controller' => 'ImportPayrollDocuments',
                'controller_url' => 'import-payroll-documents',
                'directory' => 'portal',
                'obs' => 'Upload do PDF consolidado do escritório; separação por CPF e distribuição aos colaboradores.',
                'ref' => $refImport,
            ],
            [
                'name' => 'Meus documentos de folha',
                'controller' => 'MyPayrollDocuments',
                'controller_url' => 'my-payroll-documents',
                'directory' => 'portal',
                'obs' => 'Lista folhas, recibos e informes disponibilizados pelo RH.',
                'ref' => $refPortal,
            ],
            [
                'name' => 'Visualizar documento de folha (PDF)',
                'controller' => 'ViewPayrollDocument',
                'controller_url' => 'view-payroll-document',
                'directory' => 'portal',
                'obs' => 'Stream seguro de PDF (LGPD); não usar URL pública direta ao ficheiro.',
                'ref' => $refPortal,
            ],
        ];

        foreach ($pages as $p) {
            $ref = $p['ref'];
            unset($p['ref']);
            if (!$ref) {
                continue;
            }
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $this->quote((string)$p['controller']) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }
            $gid = (int)($ref['adms_groups_page_id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => $p['directory'],
                'obs' => $p['obs'],
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
            if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
                continue;
            }
            $refId = (int)$ref['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$refId}"
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['ImportPayrollDocuments', 'MyPayrollDocuments', 'ViewPayrollDocument'] as $ctrl) {
            $row = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $this->quote($ctrl) . ' LIMIT 1');
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

    private function quote(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
