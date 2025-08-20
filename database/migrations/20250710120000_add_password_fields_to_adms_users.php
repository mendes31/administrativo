<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPasswordFieldsToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            $table
                ->addColumn('status', 'enum', [
                    'values' => ['Ativo', 'Inativo'],
                    'default' => 'Ativo',
                    'null' => false
                ])
                ->addColumn('bloqueado', 'enum', [
                    'values' => ['Sim', 'Não'],
                    'default' => 'Não',
                    'null' => false
                ])
                ->addColumn('senha_nunca_expira', 'enum', [
                    'values' => ['Sim', 'Não'],
                    'default' => 'Não',
                    'null' => false
                ])
                ->addColumn('modificar_senha_proximo_logon', 'enum', [
                    'values' => ['Sim', 'Não'],
                    'default' => 'Sim',
                    'null' => false
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $table = $this->table('adms_users');
            $table
                ->removeColumn('status')
                ->removeColumn('bloqueado')
                ->removeColumn('senha_nunca_expira')
                ->removeColumn('modificar_senha_proximo_logon')
                ->update();
        }
    }
} 