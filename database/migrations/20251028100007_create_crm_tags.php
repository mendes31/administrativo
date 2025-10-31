<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmTags extends AbstractMigration
{
    public function change()
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('crm_tags')) {
            return;
        }

        $table = $this->table('crm_tags');
        
        $table->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('color', 'string', ['limit' => 20, 'default' => '#6c757d'])
              ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              
              ->addIndex(['name'], ['unique' => true])
              
              ->create();
    }
}

