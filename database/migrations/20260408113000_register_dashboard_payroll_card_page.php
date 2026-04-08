<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterDashboardPayrollCardPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardPayrollDocuments' LIMIT 1");
        if ($exists) {
            return;
        }

        // Grupo visual no cadastro de páginas: mantém no mesmo grupo dos outros cards do dashboard.
        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'DashboardCardTempoEmpresa' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'Dashboard' LIMIT 1");
        }
        if (!$ref) {
            return;
        }

        // ACL: alinhar a "Meus documentos de folha" (não copiar Tempo de Empresa, que costuma estar mais aberto).
        $refAcl = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'MyPayrollDocuments' LIMIT 1");
        if (!$refAcl) {
            $refAcl = $ref;
        }

        $gid = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Card Dashboard - Meus Documentos',
            'controller' => 'DashboardCardPayrollDocuments',
            'controller_url' => 'dashboard-card-payroll-documents',
            'directory' => 'dashboard',
            'obs' => 'Controla visibilidade do card de Meus Documentos no dashboard.',
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

        $refAclId = (int)$refAcl['id'];
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refAclId}"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardPayrollDocuments' LIMIT 1");
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

