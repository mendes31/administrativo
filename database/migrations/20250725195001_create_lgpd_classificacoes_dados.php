<?php

use Phinx\Migration\AbstractMigration;

final class CreateLgpdClassificacoesDados extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('lgpd_classificacoes_dados', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('classificacao', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('exemplos', 'text', ['null' => true])
              ->addColumn('base_legal_id', 'integer', ['null' => false])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['classificacao'], ['unique' => true])
              ->addForeignKey('base_legal_id', 'lgpd_bases_legais', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_classificacoes_dados')->drop()->save();
    }
} 