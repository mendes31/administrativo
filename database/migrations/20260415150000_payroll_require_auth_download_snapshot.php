<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Snapshot de "exigir reautenticação para download" por documento (valor do tipo na publicação).
 */
final class PayrollRequireAuthDownloadSnapshot extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }
        $t = $this->table('adms_employee_payroll_documents');
        if (!$t->hasColumn('require_auth_download_snapshot')) {
            $t->addColumn('require_auth_download_snapshot', 'boolean', [
                'default' => false,
                'comment' => 'Cópia do tipo: exige palavra-passe antes de download (não inline)',
            ]);
            $t->update();
        }
        $this->execute('UPDATE adms_employee_payroll_documents SET require_auth_download_snapshot = 0 WHERE require_auth_download_snapshot IS NULL');
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }
        try {
            $this->execute('ALTER TABLE adms_employee_payroll_documents DROP COLUMN require_auth_download_snapshot');
        } catch (\Throwable) {
        }
    }
}
