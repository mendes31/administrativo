<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmDocuments extends AbstractMigration
{
    public function change()
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('crm_documents')) {
            return;
        }

        $table = $this->table('crm_documents');
        
        $table->addColumn('partner_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('opportunity_id', 'integer', ['null' => true, 'signed' => false])
              
              ->addColumn('file_name', 'string', ['limit' => 255])
              ->addColumn('file_path', 'string', ['limit' => 500])
              ->addColumn('file_size', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('file_type', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
              
              ->addColumn('uploaded_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('uploaded_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              
              ->addIndex(['partner_id'])
              ->addIndex(['opportunity_id'])
              
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('opportunity_id', 'crm_opportunities', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('uploaded_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

