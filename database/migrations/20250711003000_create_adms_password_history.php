<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsPasswordHistory extends AbstractMigration
{
    /**
     * Cria a tabela adms_password_history para armazenar histórico de senhas.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_password_history')) {
            $table = $this->table('adms_password_history');
            $table
                ->addColumn('user_id', 'integer', ['null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('password', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Hash da senha'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                ->addIndex(['created_at'])
                ->create();
        }
    }

    /**
     * Remove a tabela adms_password_history.
     */
    public function down(): void
    {
        $this->table('adms_password_history')->drop()->save();
    }
} 