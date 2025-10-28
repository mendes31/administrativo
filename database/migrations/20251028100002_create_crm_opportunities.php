<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmOpportunities extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('crm_opportunities');
        
        // Identificação
        $table->addColumn('code', 'string', ['limit' => 20])
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              
              // Relacionamento
              ->addColumn('partner_id', 'integer', ['signed' => false])
              ->addColumn('responsible_user_id', 'integer', ['signed' => false])
              
              // Pipeline
              ->addColumn('stage_id', 'integer', ['signed' => false])
              ->addColumn('previous_stage_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('stage_entered_at', 'datetime', ['null' => true])
              
              // Valores
              ->addColumn('value', 'decimal', ['precision' => 15, 'scale' => 2])
              ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'BRL'])
              
              // Probabilidade e Previsão
              ->addColumn('probability', 'integer', ['default' => 50, 'signed' => false])
              ->addColumn('expected_close_date', 'date', ['null' => true])
              ->addColumn('actual_close_date', 'date', ['null' => true])
              
              // Produtos/Serviços
              ->addColumn('products_services', 'text', ['null' => true])
              
              // Próxima Ação
              ->addColumn('next_action', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('next_action_date', 'datetime', ['null' => true])
              
              // Status
              ->addColumn('status', 'enum', ['values' => ['Aberta', 'Ganha', 'Perdida', 'Cancelada'], 'default' => 'Aberta'])
              ->addColumn('lost_reason', 'string', ['limit' => 255, 'null' => true])
              
              // Origem
              ->addColumn('source', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('campaign_id', 'integer', ['null' => true, 'signed' => false])
              
              // Observações
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('tags', 'string', ['limit' => 500, 'null' => true])
              
              // Auditoria
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('closed_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              // Índices
              ->addIndex(['code'], ['unique' => true])
              ->addIndex(['partner_id'])
              ->addIndex(['responsible_user_id'])
              ->addIndex(['stage_id'])
              ->addIndex(['status'])
              ->addIndex(['expected_close_date'])
              ->addIndex(['value'])
              
              // Foreign Keys
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('responsible_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('stage_id', 'crm_pipeline_stages', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('previous_stage_id', 'crm_pipeline_stages', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('closed_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

