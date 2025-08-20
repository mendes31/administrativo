<?php
use Phinx\Migration\AbstractMigration;

class AlterAdmsEvaluationAnswersStructure extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_evaluation_answers');
        
        // Remover colunas antigas
        if ($table->hasColumn('evaluation_id')) {
            $table->removeColumn('evaluation_id');
        }
        if ($table->hasColumn('user_id')) {
            $table->removeColumn('user_id');
        }
        if ($table->hasColumn('training_id')) {
            $table->removeColumn('training_id');
        }
        if ($table->hasColumn('question_id')) {
            $table->removeColumn('question_id');
        }
        if ($table->hasColumn('nota')) {
            $table->removeColumn('nota');
        }
        if ($table->hasColumn('respondido_em')) {
            $table->removeColumn('respondido_em');
        }
        
        // Adicionar novas colunas
        if (!$table->hasColumn('usuario_id')) {
            $table->addColumn('usuario_id', 'integer', ['null' => false]);
        }
        if (!$table->hasColumn('evaluation_model_id')) {
            $table->addColumn('evaluation_model_id', 'integer', ['null' => false]);
        }
        if (!$table->hasColumn('evaluation_question_id')) {
            $table->addColumn('evaluation_question_id', 'integer', ['null' => false]);
        }
        if (!$table->hasColumn('pontuacao')) {
            $table->addColumn('pontuacao', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true]);
        }
        if (!$table->hasColumn('comentario')) {
            $table->addColumn('comentario', 'text', ['null' => true]);
        }
        if (!$table->hasColumn('status')) {
            $table->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo']);
        }
        if (!$table->hasColumn('created_at')) {
            $table->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP']);
        }
        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'timestamp', ['null' => true]);
        }
        
        $table->update();
    }
} 