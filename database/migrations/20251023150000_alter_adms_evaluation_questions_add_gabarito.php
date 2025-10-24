<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para adicionar campos de gabarito e pontuação nas questões
 */
final class AlterAdmsEvaluationQuestionsAddGabarito extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_evaluation_questions');
        
        // Verificar se as colunas já existem antes de adicionar
        if (!$table->hasColumn('resposta_correta')) {
            $table->addColumn('resposta_correta', 'text', [
                'null' => true,
                'after' => 'opcoes',
                'comment' => 'Gabarito da questão para correção automática'
            ]);
        }
        
        if (!$table->hasColumn('pontos')) {
            $table->addColumn('pontos', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'default' => 1.00,
                'null' => false,
                'after' => 'resposta_correta',
                'comment' => 'Pontuação que vale a questão'
            ]);
        }
        
        if (!$table->hasColumn('explicacao')) {
            $table->addColumn('explicacao', 'text', [
                'null' => true,
                'after' => 'pontos',
                'comment' => 'Explicação/feedback exibido após responder'
            ]);
        }
        
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_evaluation_questions');
        
        if ($table->hasColumn('resposta_correta')) {
            $table->removeColumn('resposta_correta');
        }
        
        if ($table->hasColumn('pontos')) {
            $table->removeColumn('pontos');
        }
        
        if ($table->hasColumn('explicacao')) {
            $table->removeColumn('explicacao');
        }
        
        $table->update();
    }
}

