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
              // NOTA: Índice único não é criado aqui porque a coluna 'tipo_dado' é VARCHAR(255) 
              // com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
              // de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
              ->addIndex(['tipo_dado'], ['unique' => false, 'name' => 'idx_tipo_dado']) // Índice não-único para performance
              ->create();
    }

    public function down(): void
    {
        $this->table('lgpd_tipos_dados')->drop()->save();
    }
} 