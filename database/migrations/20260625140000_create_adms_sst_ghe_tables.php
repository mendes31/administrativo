<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstGheTables extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_ghe')) {
            $this->table('adms_sst_ghe')
                ->addColumn('codigo', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('ambiente_local', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Setor/área física do ambiente'])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['nome'])
                ->addIndex(['status'])
                ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_sst_ghe_codigo'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_ghe_colaboradores')) {
            $this->table('adms_sst_ghe_colaboradores')
                ->addColumn('adms_sst_ghe_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_inicio', 'date', ['null' => true])
                ->addColumn('data_fim', 'date', ['null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_sst_ghe_id'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['adms_user_id', 'data_fim'], ['name' => 'idx_ghe_colab_user_ativo'])
                ->addForeignKey('adms_sst_ghe_id', 'adms_sst_ghe', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_ghe_treinamentos')) {
            $this->table('adms_sst_ghe_treinamentos')
                ->addColumn('adms_sst_ghe_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_treinamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('validade_meses', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_sst_ghe_id', 'adms_sst_treinamento_id'], ['unique' => true, 'name' => 'uq_sst_ghe_treinamento'])
                ->addForeignKey('adms_sst_ghe_id', 'adms_sst_ghe', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_treinamento_id', 'adms_sst_treinamentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_ghe_treinamentos')) {
            $this->table('adms_sst_ghe_treinamentos')->drop()->save();
        }
        if ($this->hasTable('adms_sst_ghe_colaboradores')) {
            $this->table('adms_sst_ghe_colaboradores')->drop()->save();
        }
        if ($this->hasTable('adms_sst_ghe')) {
            $this->table('adms_sst_ghe')->drop()->save();
        }
    }
}
