<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCpfCelularToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');

            $table
                ->addColumn('cpf', 'string', [
                    'limit' => 14,
                    'null' => true,
                    'after' => 'username',
                    'comment' => 'CPF do usuário no formato 000.000.000-00'
                ])
                ->addColumn('celular', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'cpf',
                    'comment' => 'Celular do usuário, ex: (00) 00000-0000'
                ])
                ->addIndex(['cpf'], [
                    'unique' => true,
                    'name' => 'idx_adms_users_cpf_unique'
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            if ($this->hasTable('adms_users')) {
                // Remover índice único de CPF, se existir
                $table->removeIndexByName('idx_adms_users_cpf_unique');
            }
            $table
                ->removeColumn('celular')
                ->removeColumn('cpf')
                ->update();
        }
    }
}


