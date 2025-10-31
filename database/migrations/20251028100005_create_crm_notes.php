<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmNotes extends AbstractMigration
{
    public function change()
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('crm_notes')) {
            return;
        }

        $table = $this->table('crm_notes');
        
        $table->addColumn('partner_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('opportunity_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('content', 'text')
              ->addColumn('is_pinned', 'boolean', ['default' => false])
              
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              ->addIndex(['partner_id'])
              ->addIndex(['opportunity_id'])
              ->addIndex(['created_at'])
              
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('opportunity_id', 'crm_opportunities', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

