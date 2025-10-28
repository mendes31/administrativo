<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmStageHistory extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('crm_stage_history');
        
        $table->addColumn('opportunity_id', 'integer', ['signed' => false])
              ->addColumn('from_stage_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('to_stage_id', 'integer', ['signed' => false])
              ->addColumn('days_in_stage', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('moved_by', 'integer', ['signed' => false])
              ->addColumn('moved_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('notes', 'text', ['null' => true])
              
              ->addIndex(['opportunity_id'])
              ->addIndex(['moved_at'])
              
              ->addForeignKey('opportunity_id', 'crm_opportunities', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('from_stage_id', 'crm_pipeline_stages', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('to_stage_id', 'crm_pipeline_stages', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('moved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

