<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsDocumentPositions extends AbstractMigration
{
    /**
     * Cria a tabela AdmsDocumentPositions.
     *
     * Esta tabela estabelece o relacionamento entre documentos e posições/cargos,
     * definindo quais documentos são obrigatórios para cada cargo.
     *
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_document_positions')) {
            $table = $this->table('adms_document_positions');
            $table
                ->addColumn('mandatory', 'boolean', [
                    'null' => false, 
                    'default' => 0, 
                    'comment' => 'Obrigação de treinamento para o cargo: 1 - obrigatório, 0 - não obrigatório'
                ])
                ->addColumn('adms_document_id', 'integer', [
                    'null' => false, 
                    'signed' => false,
                    'comment' => 'ID do documento'
                ])
                ->addForeignKey('adms_document_id', 'adms_documents', 'id', [
                    'delete' => 'RESTRICT', 
                    'update' => 'CASCADE'
                ])
                ->addColumn('adms_position_id', 'integer', [
                    'null' => false, 
                    'signed' => false,
                    'comment' => 'ID da posição/cargo'
                ])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', [
                    'delete' => 'RESTRICT', 
                    'update' => 'CASCADE'
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
     * Remove a tabela AdmsDocumentPositions.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('adms_document_positions')->drop()->save();
    }
} 