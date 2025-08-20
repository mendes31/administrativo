<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTentativasLoginToAdmsUsers extends AbstractMigration
{
    /**
     * Adiciona o campo tentativas_login na tabela adms_users.
     */
    public function up(): void
    {
        if ($this->hasTable('adms_users') && !$this->table('adms_users')->hasColumn('tentativas_login')) {
            $this->table('adms_users')
                ->addColumn('tentativas_login', 'integer', ['default' => 0, 'null' => false, 'after' => 'bloqueado'])
                ->update();
        }
    }

    /**
     * Remove o campo tentativas_login da tabela adms_users.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_users') && $this->table('adms_users')->hasColumn('tentativas_login')) {
            $this->table('adms_users')
                ->removeColumn('tentativas_login')
                ->update();
        }
    }
} 