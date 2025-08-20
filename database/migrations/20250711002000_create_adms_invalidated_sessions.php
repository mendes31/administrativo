<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsInvalidatedSessions extends AbstractMigration
{
    /**
     * Cria a tabela adms_invalidated_sessions para controlar sessões invalidadas.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_invalidated_sessions')) {
            $table = $this->table('adms_invalidated_sessions');
            $table
                ->addColumn('user_id', 'integer', ['null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('session_id', 'string', ['limit' => 255, 'null' => false, 'comment' => 'ID da sessão invalidada'])
                ->addColumn('invalidated_at', 'datetime', ['null' => false, 'comment' => 'Data/hora da invalidação'])
                ->addColumn('reason', 'string', ['limit' => 100, 'null' => false, 'default' => 'password_change', 'comment' => 'Motivo da invalidação'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                ->addIndex(['session_id'])
                ->addIndex(['invalidated_at'])
                ->create();
        }
    }

    /**
     * Remove a tabela adms_invalidated_sessions.
     */
    public function down(): void
    {
        $this->table('adms_invalidated_sessions')->drop()->save();
    }
} 