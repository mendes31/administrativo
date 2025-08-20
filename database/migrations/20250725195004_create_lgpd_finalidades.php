<?php

use Phinx\Migration\AbstractMigration;

final class CreateLgpdFinalidades extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('lgpd_finalidades', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('finalidade', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('exemplo', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['finalidade'], ['unique' => true])
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_finalidades')->drop()->save();
    }
} 