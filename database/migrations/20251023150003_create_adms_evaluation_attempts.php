<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para criar tabela de histórico de tentativas de avaliações
 */
final class CreateAdmsEvaluationAttempts extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_evaluation_attempts')) {
            $table = $this->table('adms_evaluation_attempts');
            
            $table
                ->addColumn('assignment_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID da atribuição (adms_evaluation_assignments)'
                ])
                ->addColumn('tentativa_numero', 'integer', [
                    'null' => false,
                    'comment' => 'Número sequencial da tentativa (1, 2, 3...)'
                ])
                ->addColumn('nota_obtida', 'decimal', [
                    'precision' => 5,
                    'scale' => 2,
                    'null' => false,
                    'comment' => 'Nota final obtida (0 a 10)'
                ])
                ->addColumn('total_questoes', 'integer', [
                    'null' => false,
                    'comment' => 'Total de questões da avaliação'
                ])
                ->addColumn('questoes_corretas', 'integer', [
                    'null' => false,
                    'comment' => 'Número de questões corretas'
                ])
                ->addColumn('questoes_erradas', 'integer', [
                    'null' => false,
                    'comment' => 'Número de questões erradas'
                ])
                ->addColumn('percentual', 'decimal', [
                    'precision' => 5,
                    'scale' => 2,
                    'null' => false,
                    'comment' => 'Percentual de acerto (0 a 100)'
                ])
                ->addColumn('respostas', 'text', [
                    'null' => false,
                    'comment' => 'JSON com todas as respostas desta tentativa (armazenado como TEXT para compatibilidade com MySQL < 5.7.8)'
                ])
                ->addColumn('data_inicio', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => 'Quando o usuário iniciou a avaliação'
                ])
                ->addColumn('data_finalizacao', 'datetime', [
                    'null' => true,
                    'comment' => 'Quando o usuário finalizou'
                ])
                ->addColumn('tempo_gasto', 'integer', [
                    'null' => true,
                    'comment' => 'Tempo gasto em segundos'
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP'
                ])
                ->addIndex(['assignment_id'], ['name' => 'idx_attempt_assignment'])
                ->addIndex(['tentativa_numero'], ['name' => 'idx_attempt_number'])
                ->addIndex(['assignment_id', 'tentativa_numero'], [
                    'name' => 'idx_assignment_tentativa'
                ])
                ->addForeignKey('assignment_id', 'adms_evaluation_assignments', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION'
                ])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_evaluation_attempts')->drop()->save();
    }
}

