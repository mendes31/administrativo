<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registo de visualização/download de documentos de RH (PDF).
 *
 * Suporta accountability e demonstração de disponibilização ao titular (LGPD:
 * medidas técnicas, registo de acessos a dados sensíveis). Mantém cópia de
 * referência (tipo/ano/mês/titular) se o ficheiro for removido.
 */
final class CreatePayrollDocumentAccessLogs extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }

        if ($this->hasTable('adms_payroll_document_access_logs')) {
            return;
        }

        $this->table('adms_payroll_document_access_logs', ['id' => true, 'primary_key' => ['id']])
            ->addColumn('employee_payroll_document_id', 'integer', [
                'signed' => false,
                'null' => true,
                'comment' => 'NULL se o documento tiver sido apagado após o acesso',
            ])
            ->addColumn('owner_user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Colaborador titular do documento no momento do acesso',
            ])
            ->addColumn('viewer_user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Utilizador da sessão que abriu o PDF (titular ou administrador)',
            ])
            ->addColumn('document_type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('reference_year', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL, 'signed' => false, 'null' => false])
            ->addColumn('reference_month', 'integer', [
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'signed' => false,
                'null' => true,
            ])
            ->addColumn('delivery_mode', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'inline',
                'comment' => 'inline | attachment',
            ])
            ->addColumn('ip', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey(
                'employee_payroll_document_id',
                'adms_employee_payroll_documents',
                'id',
                ['delete' => 'SET_NULL', 'update' => 'CASCADE']
            )
            ->addIndex(['owner_user_id', 'created_at'], ['name' => 'idx_payroll_access_owner_time'])
            ->addIndex(['viewer_user_id', 'created_at'], ['name' => 'idx_payroll_access_viewer_time'])
            ->addIndex(['employee_payroll_document_id'], ['name' => 'idx_payroll_access_doc'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_payroll_document_access_logs')) {
            $this->table('adms_payroll_document_access_logs')->drop()->save();
        }
    }
}
