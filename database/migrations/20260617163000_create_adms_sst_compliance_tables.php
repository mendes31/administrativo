<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstComplianceTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_programas')) {
            $this->table('adms_sst_programas')
                ->addColumn('tipo', 'enum', [
                    'values' => ['PGR', 'PCMSO', 'PPRA', 'LTCAT', 'Outro'],
                    'default' => 'PGR',
                ])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
                ->addColumn('versao', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('vigencia_inicio', 'date', ['null' => false])
                ->addColumn('vigencia_fim', 'date', ['null' => true])
                ->addColumn('responsavel_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_medico_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', [
                    'values' => ['Rascunho', 'Vigente', 'Revogado'],
                    'default' => 'Rascunho',
                ])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['tipo'])
                ->addIndex(['status'])
                ->addIndex(['vigencia_inicio'])
                ->addIndex(['vigencia_fim'])
                ->addForeignKey('responsavel_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_medico_id', 'adms_sst_medicos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_esocial_eventos')) {
            $this->table('adms_sst_esocial_eventos')
                ->addColumn('tipo_evento', 'enum', [
                    'values' => ['S-2210', 'S-2220', 'S-2240'],
                    'null' => false,
                ])
                ->addColumn('origem_tabela', 'string', ['limit' => 80, 'null' => false])
                ->addColumn('origem_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('payload_json', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG])
                ->addColumn('status', 'enum', [
                    'values' => ['Pendente', 'Gerado', 'Enviado', 'Erro', 'Cancelado'],
                    'default' => 'Pendente',
                ])
                ->addColumn('protocolo', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('mensagem_retorno', 'text', ['null' => true])
                ->addColumn('data_geracao', 'datetime', ['null' => true])
                ->addColumn('data_envio', 'datetime', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['tipo_evento'])
                ->addIndex(['status'])
                ->addIndex(['origem_tabela', 'origem_id'])
                ->addIndex(['adms_user_id'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
