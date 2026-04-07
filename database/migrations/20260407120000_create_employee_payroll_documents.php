<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Documentos de folha/recibos (PDF por colaborador), importação em lote e metadados LGPD-friendly.
 */
final class CreateEmployeePayrollDocuments extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        if (!$this->hasTable('adms_payroll_import_batches')) {
            $batches = $this->table('adms_payroll_import_batches', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]);
            $batches
                ->addColumn('original_filename', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('document_type', 'string', ['limit' => 32, 'null' => false, 'comment' => 'payroll|vacation_receipt|ir_statement|time_bank|other'])
                ->addColumn('reference_year', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL, 'signed' => false, 'null' => false])
                ->addColumn('reference_month', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'signed' => false, 'null' => true, 'comment' => '1-12 ou null (ex.: IR anual)'])
                ->addColumn('pages_total', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('pages_matched', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('pages_unmatched', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('log_json', 'text', ['null' => true])
                ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('created_by_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addIndex(['reference_year', 'reference_month', 'document_type'], ['name' => 'idx_payroll_batches_ref'])
                ->create();
        }

        if (!$this->hasTable('adms_employee_payroll_documents')) {
            $docs = $this->table('adms_employee_payroll_documents', [
                'id' => true,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]);
            $docs
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('import_batch_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('document_type', 'string', ['limit' => 32, 'null' => false])
                ->addColumn('reference_year', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL, 'signed' => false, 'null' => false])
                ->addColumn('reference_month', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'signed' => false, 'null' => true])
                ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('storage_path', 'string', ['limit' => 512, 'null' => false, 'comment' => 'Relativo à raiz do projeto (storage/private/...)'])
                ->addColumn('file_size', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('cpf_normalized', 'char', ['limit' => 11, 'null' => true])
                ->addColumn('page_from', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL, 'signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('page_to', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL, 'signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('import_batch_id', 'adms_payroll_import_batches', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addIndex(['user_id', 'document_type', 'reference_year', 'reference_month'], ['name' => 'idx_payroll_docs_user_ref'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_employee_payroll_documents')) {
            $this->table('adms_employee_payroll_documents')->drop()->save();
        }
        if ($this->hasTable('adms_payroll_import_batches')) {
            $this->table('adms_payroll_import_batches')->drop()->save();
        }
    }
}
