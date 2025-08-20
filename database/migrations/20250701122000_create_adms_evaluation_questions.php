<?php
use Phinx\Migration\AbstractMigration;

class CreateAdmsEvaluationQuestions extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_evaluation_questions');
        $table
            ->addColumn('evaluation_model_id', 'integer')
            ->addColumn('pergunta', 'string', ['limit' => 255])
            ->addColumn('tipo', 'string', ['limit' => 20]) // objetiva/dissertativa
            ->addColumn('opcoes', 'text', ['null' => true]) // JSON para alternativas
            ->addColumn('ordem', 'integer', ['default' => 1])
            ->addTimestamps()
            ->create();
    }
} 