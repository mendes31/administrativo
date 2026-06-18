<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Matriz risco → EPI (simétrica a adms_sst_risco_exame).
 */
final class SstRiscoEpi extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_risco_epi')) {
            $this->table('adms_sst_risco_epi')
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_risco_id'])
                ->addIndex(['adms_sst_epi_id'])
                ->addIndex(['adms_sst_risco_id', 'adms_sst_epi_id'], ['unique' => true, 'name' => 'uq_sst_risco_epi'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_risco_epi')) {
            $this->table('adms_sst_risco_epi')->drop()->save();
        }
    }
}
