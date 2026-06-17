<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstInspecoesCipaPpp extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_inspecoes')) {
            $this->table('adms_sst_inspecoes')
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('tipo', 'enum', [
                    'values' => ['Rotina', 'Especial', 'CIPA', 'Outra'],
                    'default' => 'Rotina',
                ])
                ->addColumn('data_inspecao', 'date', ['null' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('local', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('inspetor_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('participantes', 'text', ['null' => true])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('conclusao', 'text', ['null' => true])
                ->addColumn('status', 'enum', [
                    'values' => ['Aberta', 'Em tratamento', 'Encerrada'],
                    'default' => 'Aberta',
                ])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['tipo'])
                ->addIndex(['data_inspecao'])
                ->addIndex(['status'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('inspetor_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_inspecao_itens')) {
            $this->table('adms_sst_inspecao_itens')
                ->addColumn('adms_sst_inspecao_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('descricao', 'text', ['null' => false])
                ->addColumn('classificacao', 'enum', [
                    'values' => ['Conforme', 'Não conforme', 'Observação'],
                    'default' => 'Observação',
                ])
                ->addColumn('acao_corretiva', 'text', ['null' => true])
                ->addColumn('responsavel_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('prazo', 'date', ['null' => true])
                ->addColumn('status', 'enum', [
                    'values' => ['Pendente', 'Em andamento', 'Concluído'],
                    'default' => 'Pendente',
                ])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_inspecao_id'])
                ->addIndex(['status'])
                ->addForeignKey('adms_sst_inspecao_id', 'adms_sst_inspecoes', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('responsavel_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_cipa_mandatos')) {
            $this->table('adms_sst_cipa_mandatos')
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('data_inicio', 'date', ['null' => false])
                ->addColumn('data_fim', 'date', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Encerrado'], 'default' => 'Ativo'])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['status'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_cipa_membros')) {
            $this->table('adms_sst_cipa_membros')
                ->addColumn('adms_sst_cipa_mandato_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('cargo', 'enum', [
                    'values' => ['Presidente', 'Vice-presidente', 'Titular', 'Suplente', 'Secretário'],
                    'default' => 'Titular',
                ])
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('ativo', 'boolean', ['default' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_cipa_mandato_id'])
                ->addIndex(['adms_user_id'])
                ->addForeignKey('adms_sst_cipa_mandato_id', 'adms_sst_cipa_mandatos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_cipa_reunioes')) {
            $this->table('adms_sst_cipa_reunioes')
                ->addColumn('adms_sst_cipa_mandato_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_reuniao', 'datetime', ['null' => false])
                ->addColumn('tipo', 'enum', ['values' => ['Ordinária', 'Extraordinária'], 'default' => 'Ordinária'])
                ->addColumn('pauta', 'text', ['null' => true])
                ->addColumn('ata', 'text', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['Agendada', 'Realizada', 'Cancelada'], 'default' => 'Agendada'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_cipa_mandato_id'])
                ->addIndex(['data_reuniao'])
                ->addForeignKey('adms_sst_cipa_mandato_id', 'adms_sst_cipa_mandatos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_ppp')) {
            $this->table('adms_sst_ppp')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('versao', 'integer', ['null' => false, 'signed' => false, 'default' => 1])
                ->addColumn('payload_json', 'text', ['null' => false, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('gerado_por', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['adms_user_id', 'versao'], ['unique' => true])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('gerado_por', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
