<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Equipamentos de segurança: tipos, checklists, equipamentos, vistorias.
 */
final class CreateAdmsSstEquipamentosTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_equipamento_tipos')) {
            $this->table('adms_sst_equipamento_tipos')
                ->addColumn('nome', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('codigo', 'string', ['limit' => 40, 'null' => false])
                ->addColumn('descricao', 'text', ['null' => true])
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

        if (!$this->hasTable('adms_sst_equipamento_checklist_itens')) {
            $this->table('adms_sst_equipamento_checklist_itens')
                ->addColumn('adms_sst_equipamento_tipo_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('ordem', 'integer', ['default' => 0, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('ativo', 'boolean', ['default' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_equipamento_tipo_id'])
                ->addForeignKey('adms_sst_equipamento_tipo_id', 'adms_sst_equipamento_tipos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_equipamentos')) {
            $this->table('adms_sst_equipamentos')
                ->addColumn('codigo', 'string', ['limit' => 60, 'null' => false])
                ->addColumn('patrimonio', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('adms_sst_equipamento_tipo_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_department_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('localizacao', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('fabricante', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('modelo', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('numero_serie', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('capacidade', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('data_fabricacao', 'date', ['null' => true])
                ->addColumn('data_recarga', 'date', ['null' => true])
                ->addColumn('data_proxima_recarga', 'date', ['null' => true])
                ->addColumn('caracteristicas', 'text', ['null' => true, 'comment' => 'JSON com campos extras'])
                ->addColumn('periodicidade_meses', 'integer', ['default' => 1, 'signed' => false, 'comment' => '1=Mensal,2=Bimestral,3=Trimestral,6=Semestral,12=Anual'])
                ->addColumn('data_referencia_inspecao', 'date', ['null' => true, 'comment' => 'Mês âncora da 1ª competência'])
                ->addColumn('responsavel_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo', 'Baixado'], 'default' => 'Ativo'])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['status'])
                ->addIndex(['periodicidade_meses'])
                ->addForeignKey('adms_sst_equipamento_tipo_id', 'adms_sst_equipamento_tipos', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('responsavel_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_equipamento_vistorias')) {
            $this->table('adms_sst_equipamento_vistorias')
                ->addColumn('adms_sst_equipamento_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('competencia', 'string', ['limit' => 7, 'null' => false, 'comment' => 'YYYY-MM'])
                ->addColumn('data_prevista', 'date', ['null' => false])
                ->addColumn('data_realizada', 'datetime', ['null' => true])
                ->addColumn('executor_adms_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', [
                    'values' => ['Pendente', 'Em andamento', 'Concluída', 'Vencida', 'Cancelada'],
                    'default' => 'Pendente',
                ])
                ->addColumn('resultado', 'enum', [
                    'values' => ['Conforme', 'Não conforme'],
                    'null' => true,
                ])
                ->addColumn('observacao', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_equipamento_id'])
                ->addIndex(['competencia'])
                ->addIndex(['status'])
                ->addIndex(['adms_sst_equipamento_id', 'competencia'], ['unique' => true, 'name' => 'uq_equipamento_competencia'])
                ->addForeignKey('adms_sst_equipamento_id', 'adms_sst_equipamentos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('executor_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_equipamento_vistoria_respostas')) {
            $this->table('adms_sst_equipamento_vistoria_respostas')
                ->addColumn('adms_sst_equipamento_vistoria_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_equipamento_checklist_item_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('descricao_snapshot', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('ordem', 'integer', ['default' => 0, 'signed' => false])
                ->addColumn('resposta', 'enum', [
                    'values' => ['Conforme', 'Não conforme', 'N/A'],
                    'null' => true,
                ])
                ->addColumn('observacao', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_equipamento_vistoria_id'])
                ->addForeignKey('adms_sst_equipamento_vistoria_id', 'adms_sst_equipamento_vistorias', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_equipamento_checklist_item_id', 'adms_sst_equipamento_checklist_itens', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
