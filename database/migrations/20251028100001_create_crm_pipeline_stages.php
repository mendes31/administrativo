<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmPipelineStages extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('crm_pipeline_stages');
        
        $table->addColumn('name', 'string', ['limit' => 100])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('display_order', 'integer')
              ->addColumn('color', 'string', ['limit' => 20, 'default' => '#6c757d'])
              ->addColumn('conversion_probability', 'integer', ['default' => 0])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('is_final_stage', 'boolean', ['default' => false])
              ->addColumn('stage_type', 'enum', ['values' => ['active', 'won', 'lost'], 'default' => 'active'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              ->addIndex(['display_order'])
              ->addIndex(['is_active'])
              
              ->create();
    }
}

