<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para criar tabela de atribuições de avaliações para usuários
 */
final class CreateAdmsEvaluationAssignments extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_evaluation_assignments')) {
            $table = $this->table('adms_evaluation_assignments');
            
            $table
                ->addColumn('evaluation_model_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID do modelo de avaliação'
                ])
                ->addColumn('adms_user_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID do usuário que deve responder'
                ])
                ->addColumn('created_by', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'ID do usuário que atribuiu'
                ])
                ->addColumn('data_atribuicao', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => 'Data/hora da atribuição'
                ])
                ->addColumn('data_limite', 'date', [
                    'null' => true,
                    'comment' => 'Prazo para conclusão'
                ])
                ->addColumn('status', 'enum', [
                    'values' => ['pendente', 'em_andamento', 'concluido', 'aprovado', 'reprovado', 'cancelado'],
                    'default' => 'pendente',
                    'null' => false,
                    'comment' => 'Status atual da atribuição'
                ])
                ->addColumn('tentativas', 'integer', [
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Número de tentativas realizadas'
                ])
                ->addColumn('nota_maxima', 'decimal', [
                    'precision' => 5,
                    'scale' => 2,
                    'null' => true,
                    'comment' => 'Melhor nota obtida pelo usuário'
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP'
                ])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP'
                ])
                ->addIndex(['evaluation_model_id'], ['name' => 'idx_assignment_model'])
                ->addIndex(['adms_user_id'], ['name' => 'idx_assignment_user'])
                ->addIndex(['status'], ['name' => 'idx_assignment_status'])
                ->addIndex(['data_limite'], ['name' => 'idx_assignment_deadline'])
                ->addIndex(['evaluation_model_id', 'adms_user_id'], [
                    'unique' => true,
                    'name' => 'unique_model_user'
                ])
                ->addForeignKey('evaluation_model_id', 'adms_evaluation_models', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION'
                ])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION'
                ])
                ->addForeignKey('created_by', 'adms_users', 'id', [
                    'delete' => 'NO_ACTION',
                    'update' => 'NO_ACTION'
                ])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_evaluation_assignments')->drop()->save();
    }
}

