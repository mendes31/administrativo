<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Catálogos SST: exames, EPIs, riscos, CIDs e médicos.
 */
final class CreateAdmsSstCatalogTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_exames')) {
            $this->table('adms_sst_exames')
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('periodicidade_meses', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Periodicidade padrão em meses'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['nome'])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_epis')) {
            $this->table('adms_sst_epis')
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('ca_numero', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Certificado de Aprovação'])
                ->addColumn('ca_validade', 'date', ['null' => true])
                ->addColumn('estoque_atual', 'integer', ['default' => 0, 'signed' => false])
                ->addColumn('estoque_minimo', 'integer', ['default' => 0, 'signed' => false])
                ->addColumn('periodicidade_troca_dias', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['nome'])
                ->addIndex(['ca_numero'])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_riscos')) {
            $this->table('adms_sst_riscos')
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('tipo', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Físico, químico, biológico, ergonômico, acidente'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['nome'])
                ->addIndex(['tipo'])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_cids')) {
            $this->table('adms_sst_cids')
                ->addColumn('codigo', 'string', ['limit' => 10, 'null' => false])
                ->addColumn('descricao', 'string', ['limit' => 500, 'null' => false])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_medicos')) {
            $this->table('adms_sst_medicos')
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('crm', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('crm_uf', 'string', ['limit' => 2, 'null' => true])
                ->addColumn('clinica', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('telefone', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['nome'])
                ->addIndex(['crm'])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
