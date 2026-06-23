<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstTreinamentosTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_treinamentos')) {
            $this->table('adms_sst_treinamentos')
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('nr_referencia', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Ex.: NR-10, NR-35, NR-5'])
                ->addColumn('tipo', 'enum', [
                    'values' => ['Inicial', 'Reciclagem', 'Ambos'],
                    'default' => 'Ambos',
                ])
                ->addColumn('modalidade', 'enum', [
                    'values' => ['Presencial', 'EAD', 'Hibrido'],
                    'default' => 'Presencial',
                ])
                ->addColumn('carga_horaria_minutos', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('validade_meses', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Periodicidade reciclagem'])
                ->addColumn('prazo_primeiro_dias', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Prazo para 1º treinamento após vínculo'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['nome'])
                ->addIndex(['status'])
                ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_sst_treinamentos_codigo'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_treinamento_necessidade')) {
            $this->table('adms_sst_treinamento_necessidade')
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_treinamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('validade_meses', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_position_id'])
                ->addIndex(['adms_department_id'])
                ->addIndex(['adms_sst_risco_id'])
                ->addIndex(['adms_sst_treinamento_id'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_treinamento_id', 'adms_sst_treinamentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_risco_treinamento')) {
            $this->table('adms_sst_risco_treinamento')
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_treinamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('validade_meses', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_sst_risco_id', 'adms_sst_treinamento_id'], ['unique' => true, 'name' => 'uq_sst_risco_treinamento'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_treinamento_id', 'adms_sst_treinamentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_treinamento_vinculos')) {
            $this->table('adms_sst_treinamento_vinculos')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_treinamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('status', 'enum', [
                    'values' => ['pendente', 'agendado', 'concluido', 'vencido', 'proximo_vencimento', 'dentro_do_prazo'],
                    'default' => 'pendente',
                ])
                ->addColumn('motivo', 'enum', [
                    'values' => ['primeiro', 'reciclagem', 'retreinamento'],
                    'default' => 'primeiro',
                ])
                ->addColumn('data_agendada', 'date', ['null' => true])
                ->addColumn('data_realizacao', 'date', ['null' => true])
                ->addColumn('data_validade', 'date', ['null' => true])
                ->addColumn('data_limite_primeiro', 'date', ['null' => true])
                ->addColumn('nota', 'decimal', ['precision' => 4, 'scale' => 2, 'null' => true])
                ->addColumn('certificado', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_user_id'])
                ->addIndex(['adms_sst_treinamento_id'])
                ->addIndex(['status'])
                ->addIndex(['adms_user_id', 'adms_sst_treinamento_id'], ['unique' => true, 'name' => 'uq_sst_treinamento_vinculo_user'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_treinamento_id', 'adms_sst_treinamentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_treinamento_aplicacoes')) {
            $this->table('adms_sst_treinamento_aplicacoes')
                ->addColumn('adms_sst_treinamento_vinculo_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_treinamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_realizacao', 'date', ['null' => true])
                ->addColumn('data_agendada', 'date', ['null' => true])
                ->addColumn('nota', 'decimal', ['precision' => 4, 'scale' => 2, 'null' => true])
                ->addColumn('instrutor_nome', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('instrutor_registro', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('modalidade_aplicada', 'enum', [
                    'values' => ['Presencial', 'EAD', 'Hibrido'],
                    'null' => true,
                ])
                ->addColumn('certificado', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['agendado', 'concluido'], 'default' => 'concluido'])
                ->addColumn('aplicado_por', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['adms_user_id'])
                ->addIndex(['adms_sst_treinamento_id'])
                ->addIndex(['data_realizacao'])
                ->addForeignKey('adms_sst_treinamento_vinculo_id', 'adms_sst_treinamento_vinculos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('adms_sst_treinamento_id', 'adms_sst_treinamentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('aplicado_por', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->create();
        }
    }
}
