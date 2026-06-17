<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regras SST por cargo/setor/risco e anexos polimórficos.
 */
final class CreateAdmsSstRulesAndAttachments extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_epi_necessidade')) {
            $this->table('adms_sst_epi_necessidade')
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_position_id'])
                ->addIndex(['adms_department_id'])
                ->addIndex(['adms_sst_risco_id'])
                ->addIndex(['adms_sst_epi_id'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_exame_necessidade')) {
            $this->table('adms_sst_exame_necessidade')
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_exame_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('periodicidade_meses', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_position_id'])
                ->addIndex(['adms_department_id'])
                ->addIndex(['adms_sst_risco_id'])
                ->addIndex(['adms_sst_exame_id'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_exame_id', 'adms_sst_exames', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_riscos_cargo')) {
            $this->table('adms_sst_riscos_cargo')
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('nivel', 'enum', ['values' => ['Baixo', 'Médio', 'Alto', 'Crítico'], 'default' => 'Médio'])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_position_id'])
                ->addIndex(['adms_department_id'])
                ->addIndex(['adms_sst_risco_id'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_anexos')) {
            $this->table('adms_sst_anexos')
                ->addColumn('entity_type', 'string', ['limit' => 50, 'null' => false, 'comment' => 'aso, afastamento, epi_entrega, acidente'])
                ->addColumn('entity_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('file_name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('file_path', 'string', ['limit' => 500, 'null' => false])
                ->addColumn('mime_type', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('file_size', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('uploaded_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['entity_type', 'entity_id'])
                ->addForeignKey('uploaded_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
