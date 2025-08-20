<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExemploSenhaToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->addColumn('exemplo_senha', 'string', ['default' => '', 'null' => false, 'limit' => 255])
                  ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->removeColumn('exemplo_senha')->update();
        }
    }
} 