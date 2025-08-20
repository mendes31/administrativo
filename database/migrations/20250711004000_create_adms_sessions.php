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
                ->addIndex(['session_id'], ['unique' => true])
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