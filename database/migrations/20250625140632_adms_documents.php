<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsDocuments extends AbstractMigration
{
    /**
     * Cria a tabela AdmsDocuments.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_documents` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `id`: ID único auto incremento (chave primária)
     * - `cod_doc`: Código do documento (não pode ser nulo)
     * - `name_doc`: Nome do documento (não pode ser nulo)
     * - `version`: Versão do documento (pode ser nulo)
     * - `active`: Status ativo/inativo (padrão: 1 - ativo)
     * - `created_at`: Timestamp da criação do registro
     * - `updated_at`: Timestamp da última atualização do registro
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_documents' não existe no banco de dados
        if (!$this->hasTable('adms_documents')) {
            // Cria a tabela 'adms_documents'
            $table = $this->table('adms_documents');

            // Define as colunas da tabela
            $table->addColumn('cod_doc', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Código do documento'
            ])
            ->addColumn('name_doc', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Nome do documento'
            ])
            ->addColumn('version', 'string', [
                'limit' => 50,
                'null' => true,
                'comment' => 'Versão do documento'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => 1,
                'comment' => 'Status ativo 1 e inativo 0'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data e hora de criação'
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data e hora da última atualização'
            ])
            ->create();
        }
    }

    /**
     * Reverte a criação da tabela AdmsDocuments.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_documents` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_documents' do banco de dados
        $this->table('adms_documents')->drop()->save();
    }
} 