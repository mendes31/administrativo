<?php
use Phinx\Migration\AbstractMigration;

class CreateAdmsEvaluationAnswers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_evaluation_answers');
        $table
            ->addColumn('evaluation_id', 'integer') // FK para adms_training_evaluations
            ->addColumn('user_id', 'integer')
            ->addColumn('training_id', 'integer')
            ->addColumn('question_id', 'integer')
            ->addColumn('resposta', 'text', ['null' => true])
            ->addColumn('nota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
            ->addColumn('respondido_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
} 