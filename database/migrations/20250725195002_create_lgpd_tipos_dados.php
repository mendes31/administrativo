<?php

use Phinx\Migration\AbstractMigration;

final class CreateLgpdTiposDados extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('lgpd_tipos_dados', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('tipo_dado', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('exemplos', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['tipo_dado'], ['unique' => true])
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_tipos_dados')->drop()->save();
    }
} 