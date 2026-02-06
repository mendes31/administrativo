<?php

use Phinx\Migration\AbstractMigration;

final class CreateLgpdCategoriasTitulares extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('lgpd_categorias_titulares', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('titular', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('exemplo', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo'], 'default' => 'Ativo', 'null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
              // NOTA: Índice único não é criado aqui porque a coluna 'titular' é VARCHAR(255) 
              // com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
              // de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
              ->addIndex(['titular'], ['unique' => false, 'name' => 'idx_titular']) // Índice não-único para performance
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_categorias_titulares')->drop()->save();
    }
} 