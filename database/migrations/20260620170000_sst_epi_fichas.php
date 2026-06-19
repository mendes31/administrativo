<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fichas de entrega de EPI (cabeçalho + itens) com PDF e assinatura no portal.
 */
final class SstEpiFichas extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_epi_fichas')) {
            $this->table('adms_sst_epi_fichas')
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_entrega', 'date', ['null' => false])
                ->addColumn('status_assinatura', 'enum', [
                    'values' => ['Pendente', 'Assinado', 'Cancelado'],
                    'default' => 'Pendente',
                ])
                ->addColumn('pdf_storage_path', 'string', ['limit' => 500, 'null' => true])
                ->addColumn('pdf_hash_sha256', 'char', ['limit' => 64, 'null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('signed_at', 'datetime', ['null' => true])
                ->addColumn('signed_ip', 'string', ['limit' => 45, 'null' => true])
                ->addColumn('signed_user_agent', 'string', ['limit' => 512, 'null' => true])
                ->addColumn('signed_auth_method', 'string', ['limit' => 40, 'null' => true, 'comment' => 'session = portal logado'])
                ->addColumn('signed_document_hash_sha256', 'char', ['limit' => 64, 'null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_user_id'])
                ->addIndex(['data_entrega'])
                ->addIndex(['status_assinatura'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_epi_ficha_itens')) {
            $this->table('adms_sst_epi_ficha_itens')
                ->addColumn('adms_sst_epi_ficha_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('quantidade', 'integer', ['default' => 1, 'signed' => false])
                ->addColumn('ca_utilizado', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('data_prevista_troca', 'date', ['null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_epi_ficha_id'])
                ->addIndex(['adms_sst_epi_id'])
                ->addForeignKey('adms_sst_epi_ficha_id', 'adms_sst_epi_fichas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }

        if ($this->hasTable('adms_sst_epi_entregas') && !$this->table('adms_sst_epi_entregas')->hasColumn('adms_sst_epi_ficha_id')) {
            $this->table('adms_sst_epi_entregas')
                ->addColumn('adms_sst_epi_ficha_id', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'adms_sst_epi_id',
                    'comment' => 'Origem na ficha assinada',
                ])
                ->addIndex(['adms_sst_epi_ficha_id'])
                ->addForeignKey('adms_sst_epi_ficha_id', 'adms_sst_epi_fichas', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->update();
        }
    }
}
