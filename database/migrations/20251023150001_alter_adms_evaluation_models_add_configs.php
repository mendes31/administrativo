<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para adicionar configurações nos modelos de avaliação
 */
final class AlterAdmsEvaluationModelsAddConfigs extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_evaluation_models');
        
        // Nota mínima para aprovação
        if (!$table->hasColumn('nota_minima_aprovacao')) {
            $table->addColumn('nota_minima_aprovacao', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'default' => 7.00,
                'null' => false,
                'after' => 'ativo',
                'comment' => 'Nota mínima para ser aprovado (0 a 10)'
            ]);
        }
        
        // Permitir refazer a avaliação
        if (!$table->hasColumn('permitir_refazer')) {
            $table->addColumn('permitir_refazer', 'boolean', [
                'default' => 1,
                'null' => false,
                'after' => 'nota_minima_aprovacao',
                'comment' => 'Permite que o usuário refaça se reprovar'
            ]);
        }
        
        // Número máximo de tentativas
        if (!$table->hasColumn('max_tentativas')) {
            $table->addColumn('max_tentativas', 'integer', [
                'null' => true,
                'after' => 'permitir_refazer',
                'comment' => 'Máximo de tentativas (NULL = ilimitado)'
            ]);
        }
        
        // Mostrar gabarito ao finalizar
        if (!$table->hasColumn('mostrar_gabarito')) {
            $table->addColumn('mostrar_gabarito', 'boolean', [
                'default' => 1,
                'null' => false,
                'after' => 'max_tentativas',
                'comment' => 'Exibir gabarito após responder'
            ]);
        }
        
        // Embaralhar questões
        if (!$table->hasColumn('embaralhar_questoes')) {
            $table->addColumn('embaralhar_questoes', 'boolean', [
                'default' => 0,
                'null' => false,
                'after' => 'mostrar_gabarito',
                'comment' => 'Randomizar ordem das questões'
            ]);
        }
        
        // Tempo limite em minutos
        if (!$table->hasColumn('tempo_limite')) {
            $table->addColumn('tempo_limite', 'integer', [
                'null' => true,
                'after' => 'embaralhar_questoes',
                'comment' => 'Tempo limite em minutos (NULL = sem limite)'
            ]);
        }
        
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_evaluation_models');
        
        $columns = [
            'nota_minima_aprovacao',
            'permitir_refazer',
            'max_tentativas',
            'mostrar_gabarito',
            'embaralhar_questoes',
            'tempo_limite'
        ];
        
        foreach ($columns as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        
        $table->update();
    }
}

