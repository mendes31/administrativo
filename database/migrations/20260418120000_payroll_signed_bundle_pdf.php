<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * PDF unificado após ciência: cópia do documento original + página(s) de trilha de assinatura (estilo pacote comprovante).
 * O ficheiro em storage_path permanece o original; signed_bundle_storage_path aponta para o pacote opcional.
 */
final class PayrollSignedBundlePdf extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }
        $t = $this->table('adms_employee_payroll_documents');
        if (!$t->hasColumn('signed_bundle_storage_path')) {
            $t->addColumn('signed_bundle_storage_path', 'string', [
                'limit' => 512,
                'null' => true,
                'comment' => 'PDF original + trilha de assinatura (relativo à raiz do projeto)',
            ]);
        }
        $t->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }
        $t = $this->table('adms_employee_payroll_documents');
        if ($t->hasColumn('signed_bundle_storage_path')) {
            $t->removeColumn('signed_bundle_storage_path');
        }
        $t->update();
    }
}
