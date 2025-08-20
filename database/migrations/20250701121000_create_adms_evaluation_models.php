<?php
use Phinx\Migration\AbstractMigration;

class CreateAdmsEvaluationModels extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_evaluation_models');
        $table
            ->addColumn('adms_training_id', 'integer')
            ->addColumn('titulo', 'string', ['limit' => 255])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['default' => true])
            ->addTimestamps()
            ->create();
    }
} 