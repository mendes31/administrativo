<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSessions extends AbstractMigration
{
    /**
     * Cria a tabela adms_sessions para controlar sessões ativas dos usuários.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_sessions')) {
            $table = $this->table('adms_sessions');
            $table
                ->addColumn('user_id', 'integer', ['null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('session_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID da sessão'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                // NOTA: Índice único não é criado aqui porque a coluna 'session_id' é VARCHAR(255) 
                // com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
                // de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
                ->addIndex(['session_id'], ['unique' => false, 'name' => 'idx_session_id']) // Índice não-único para performance
                ->create();
        }
    }

    /**
     * Remove a tabela adms_sessions.
     */
    public function down(): void
    {
        $this->table('adms_sessions')->drop()->save();
    }
} 