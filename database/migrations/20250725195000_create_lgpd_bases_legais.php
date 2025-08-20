<?php

use Phinx\Migration\AbstractMigration;

final class CreateLgpdBasesLegais extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('lgpd_bases_legais', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('base_legal', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('descricao', 'text', ['null' => true])
              ->addColumn('exemplo', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['base_legal'], ['unique' => true])
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_bases_legais')->drop()->save();
    }
} 