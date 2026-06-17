<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registros operacionais SST: ASO, afastamentos, entregas de EPI e acidentes.
 */
final class CreateAdmsSstOperationalTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_asos')) {
            $this->table('adms_sst_asos')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_exame_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Exame principal vinculado'])
                ->addColumn('adms_sst_medico_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('tipo', 'enum', [
                    'values' => ['Admissional', 'Periódico', 'Mudança de função', 'Retorno ao trabalho', 'Demissional'],
                    'null' => false,
                ])
                ->addColumn('data_realizacao', 'date', ['null' => false])
                ->addColumn('data_validade', 'date', ['null' => true])
                ->addColumn('resultado', 'enum', [
                    'values' => ['Apto', 'Inapto', 'Apto com restrição'],
                    'default' => 'Apto',
                ])
                ->addColumn('restricoes', 'text', ['null' => true])
                ->addColumn('clinica', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['tipo'])
                ->addIndex(['data_realizacao'])
                ->addIndex(['data_validade'])
                ->addIndex(['resultado'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_exame_id', 'adms_sst_exames', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_medico_id', 'adms_sst_medicos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_afastamentos')) {
            $this->table('adms_sst_afastamentos')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_cid_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('adms_sst_medico_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('tipo', 'enum', [
                    'values' => ['Doença', 'Acidente de trabalho', 'Licença', 'Maternidade', 'Outro'],
                    'default' => 'Doença',
                ])
                ->addColumn('data_inicio', 'date', ['null' => false])
                ->addColumn('data_fim', 'date', ['null' => true])
                ->addColumn('dias_afastamento', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('data_retorno', 'date', ['null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Encerrado'], 'default' => 'Ativo'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['tipo'])
                ->addIndex(['data_inicio'])
                ->addIndex(['data_fim'])
                ->addIndex(['status'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_cid_id', 'adms_sst_cids', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_medico_id', 'adms_sst_medicos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_epi_entregas')) {
            $this->table('adms_sst_epi_entregas')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('tipo_movimento', 'enum', [
                    'values' => ['Entrega', 'Devolução', 'Substituição', 'Perda/Dano'],
                    'default' => 'Entrega',
                ])
                ->addColumn('quantidade', 'integer', ['default' => 1, 'signed' => false])
                ->addColumn('data_movimento', 'date', ['null' => false])
                ->addColumn('data_prevista_troca', 'date', ['null' => true])
                ->addColumn('termo_assinado', 'boolean', ['default' => false])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['adms_sst_epi_id'])
                ->addIndex(['tipo_movimento'])
                ->addIndex(['data_movimento'])
                ->addIndex(['data_prevista_troca'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_acidentes')) {
            $this->table('adms_sst_acidentes')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_cid_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('tipo', 'enum', ['values' => ['Acidente', 'Incidente', 'Quase acidente'], 'default' => 'Acidente'])
                ->addColumn('data_ocorrencia', 'datetime', ['null' => false])
                ->addColumn('local', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('descricao', 'text', ['null' => false])
                ->addColumn('cat_numero', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('cat_data', 'date', ['null' => true])
                ->addColumn('investigacao', 'text', ['null' => true])
                ->addColumn('plano_acao', 'text', ['null' => true])
                ->addColumn('status', 'enum', ['values' => ['Aberto', 'Em investigação', 'Encerrado'], 'default' => 'Aberto'])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['tipo'])
                ->addIndex(['data_ocorrencia'])
                ->addIndex(['status'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_cid_id', 'adms_sst_cids', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
