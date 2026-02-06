<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsCollaborator extends AbstractMigration
{
    /**
     * Cria a tabela AdmsCollaborator.
     *
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_collaborator')) {
            $table = $this->table('adms_collaborator');
            $table
                ->addColumn('name', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'comment' => 'Nome do colaborador'
                ])
                ->addColumn('email', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'comment' => 'E-mail do colaborador',
                ])
                // NOTA: Índice único não é criado aqui porque a coluna 'email' é VARCHAR(255) 
                // com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
                // de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
                ->addIndex(['email'], ['unique' => false, 'name' => 'idx_email']) // Índice não-único para performance
                ->addColumn('col_id_position', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID do cargo (posição)'
                ])
                ->addColumn('col_department_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID do departamento'
                ])
                ->addColumn('col_image', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'comment' => 'Imagem do colaborador'
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
                ->addForeignKey('col_id_position', 'adms_positions', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ])
                ->addForeignKey('col_department_id', 'adms_departments', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'CASCADE',
                ])
                ->create();
        }
    }

    /**
     * Remove a tabela AdmsCollaborator.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('adms_collaborator')->drop()->save();
    }
} 