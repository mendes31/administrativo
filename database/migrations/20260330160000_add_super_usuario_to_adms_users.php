<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSuperUsuarioToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            $this->table('adms_users')
                ->addColumn('super_usuario', 'boolean', ['default' => false, 'null' => false])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            $this->table('adms_users')
                ->removeColumn('super_usuario')
                ->update();
        }
    }
}
