<?php

use Phinx\Migration\AbstractMigration;

class CreateLgpdFontesColeta extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lgpd_fontes_coleta', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('descricao', 'text', ['null' => true])
              ->addColumn('ativo', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['null' => true])
              ->create();
    }
} 