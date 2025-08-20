<?php
use Phinx\Migration\AbstractMigration;

class AlterAdmsTrainingEvaluations extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_training_evaluations');
        if (!$table->hasColumn('adms_training_id')) {
            $table->addColumn('adms_training_id', 'integer', ['null' => true, 'after' => 'id']);
        }
        if (!$table->hasColumn('evaluation_model_id')) {
            $table->addColumn('evaluation_model_id', 'integer', ['null' => true, 'after' => 'adms_training_id']);
        }
        if (!$table->hasColumn('nota_geral')) {
            $table->addColumn('nota_geral', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'after' => 'respostas']);
        }
        $table->update();
    }
} 