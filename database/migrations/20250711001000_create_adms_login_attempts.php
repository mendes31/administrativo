<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsLoginAttempts extends AbstractMigration
{
    /**
     * Cria a tabela adms_login_attempts para registrar tentativas de login.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_login_attempts')) {
            $table = $this->table('adms_login_attempts');
            $table
                ->addColumn('user_id', 'integer', ['null' => true, 'comment' => 'ID do usuário, se existir'])
                ->addColumn('username_tentado', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => false])
                ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('data_tentativa', 'datetime', ['null' => false])
                ->addColumn('resultado', 'string', ['limit' => 30, 'null' => false, 'comment' => 'SUCCESS, WRONG_PASSWORD, USER_NOT_FOUND, BLOCKED'])
                ->addColumn('detalhes', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }
    }

    /**
     * Remove a tabela adms_login_attempts.
     */
    public function down(): void
    {
        $this->table('adms_login_attempts')->drop()->save();
    }
} 