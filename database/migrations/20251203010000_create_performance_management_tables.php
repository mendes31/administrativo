<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePerformanceManagementTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Avaliações de Desempenho (90°, 180°, 360°)
        if (!$this->hasTable('adms_performance_reviews')) {
            $table = $this->table('adms_performance_reviews', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador avaliado'])
                ->addColumn('reviewer_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do avaliador'])
                ->addColumn('review_type', 'string', ['limit' => 20, 'comment' => 'Tipo: 90, 180, 360, annual'])
                ->addColumn('review_period_start', 'date', ['null' => false, 'comment' => 'Início do período avaliado'])
                ->addColumn('review_period_end', 'date', ['null' => false, 'comment' => 'Fim do período avaliado'])
                ->addColumn('review_date', 'date', ['null' => false, 'comment' => 'Data da avaliação'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft', 'comment' => 'Status: draft, in_progress, completed, cancelled'])
                ->addColumn('overall_score', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'Nota geral (0-10)'])
                ->addColumn('strengths', 'text', ['null' => true, 'comment' => 'Pontos fortes'])
                ->addColumn('improvements', 'text', ['null' => true, 'comment' => 'Pontos de melhoria'])
                ->addColumn('comments', 'text', ['null' => true, 'comment' => 'Comentários gerais'])
                ->addColumn('employee_comments', 'text', ['null' => true, 'comment' => 'Comentários do colaborador'])
                ->addColumn('evaluation_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da avaliação relacionada'])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que criou'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('completed_at', 'datetime', ['null' => true, 'comment' => 'Data de conclusão'])
                
                ->addIndex(['employee_id'])
                ->addIndex(['reviewer_id'])
                ->addIndex(['review_type'])
                ->addIndex(['status'])
                ->addIndex(['review_period_start', 'review_period_end'])
                ->addIndex(['evaluation_id'])
                
                ->addForeignKey('employee_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('reviewer_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Competências (reutilizável para matriz)
        if (!$this->hasTable('adms_competencies')) {
            $table = $this->table('adms_competencies', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('name', 'string', ['limit' => 255, 'comment' => 'Nome da competência'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição'])
                ->addColumn('competency_type', 'string', ['limit' => 50, 'comment' => 'Tipo: technical, behavioral, leadership'])
                ->addColumn('category', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria'])
                ->addColumn('level_1_description', 'text', ['null' => true, 'comment' => 'Descrição nível 1'])
                ->addColumn('level_2_description', 'text', ['null' => true, 'comment' => 'Descrição nível 2'])
                ->addColumn('level_3_description', 'text', ['null' => true, 'comment' => 'Descrição nível 3'])
                ->addColumn('level_4_description', 'text', ['null' => true, 'comment' => 'Descrição nível 4'])
                ->addColumn('level_5_description', 'text', ['null' => true, 'comment' => 'Descrição nível 5'])
                ->addColumn('status', 'boolean', ['default' => true, 'comment' => 'Ativa'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['competency_type'])
                ->addIndex(['category'])
                ->addIndex(['status'])
                
                ->create();
        }
        
        // Tabela de Avaliação de Competências por Colaborador
        if (!$this->hasTable('adms_performance_competencies')) {
            $table = $this->table('adms_performance_competencies', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('performance_review_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da avaliação'])
                ->addColumn('competency_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da competência'])
                ->addColumn('current_level', 'integer', ['default' => 1, 'comment' => 'Nível atual (1-5)'])
                ->addColumn('target_level', 'integer', ['default' => 3, 'comment' => 'Nível alvo (1-5)'])
                ->addColumn('assessed_level', 'integer', ['null' => true, 'comment' => 'Nível avaliado (1-5)'])
                ->addColumn('comments', 'text', ['null' => true, 'comment' => 'Comentários'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['performance_review_id'])
                ->addIndex(['competency_id'])
                
                ->addForeignKey('performance_review_id', 'adms_performance_reviews', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('competency_id', 'adms_competencies', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Matriz de Competências por Cargo
        if (!$this->hasTable('adms_competency_matrix')) {
            $table = $this->table('adms_competency_matrix', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('position_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do cargo'])
                ->addColumn('competency_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da competência'])
                ->addColumn('required_level', 'integer', ['default' => 3, 'comment' => 'Nível requerido (1-5)'])
                ->addColumn('is_mandatory', 'boolean', ['default' => false, 'comment' => 'Obrigatória'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['position_id'])
                ->addIndex(['competency_id'])
                ->addIndex(['position_id', 'competency_id'], ['unique' => true])
                
                ->addForeignKey('position_id', 'adms_positions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('competency_id', 'adms_competencies', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Metas/OKRs (pode reutilizar do PDI, mas criando específica para performance)
        if (!$this->hasTable('adms_performance_goals')) {
            $table = $this->table('adms_performance_goals', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('performance_review_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da avaliação (opcional)'])
                ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador'])
                ->addColumn('goal_title', 'string', ['limit' => 255, 'comment' => 'Título da meta'])
                ->addColumn('goal_description', 'text', ['null' => true, 'comment' => 'Descrição'])
                ->addColumn('goal_type', 'string', ['limit' => 50, 'default' => 'individual', 'comment' => 'Tipo: individual, team, company'])
                ->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Valor alvo'])
                ->addColumn('current_value', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'Valor atual'])
                ->addColumn('unit', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Unidade de medida'])
                ->addColumn('deadline', 'date', ['null' => true, 'comment' => 'Prazo'])
                ->addColumn('weight', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 1.0, 'comment' => 'Peso da meta'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, in_progress, achieved, failed'])
                ->addColumn('progress_percentage', 'integer', ['default' => 0, 'comment' => 'Percentual (0-100)'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('achieved_at', 'datetime', ['null' => true, 'comment' => 'Data de alcance'])
                
                ->addIndex(['performance_review_id'])
                ->addIndex(['employee_id'])
                ->addIndex(['status'])
                ->addIndex(['deadline'])
                ->addIndex(['goal_type'])
                
                ->addForeignKey('performance_review_id', 'adms_performance_reviews', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('employee_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Feedbacks (pode reutilizar do PDI, mas criando específica para performance)
        if (!$this->hasTable('adms_performance_feedbacks')) {
            $table = $this->table('adms_performance_feedbacks', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('employee_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador'])
                ->addColumn('given_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID de quem deu o feedback'])
                ->addColumn('feedback_type', 'string', ['limit' => 50, 'default' => 'general', 'comment' => 'Tipo: general, performance, recognition, improvement'])
                ->addColumn('feedback_text', 'text', ['null' => false, 'comment' => 'Texto do feedback'])
                ->addColumn('is_anonymous', 'boolean', ['default' => false, 'comment' => 'Feedback anônimo'])
                ->addColumn('is_public', 'boolean', ['default' => false, 'comment' => 'Feedback público'])
                ->addColumn('related_review_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da avaliação relacionada'])
                ->addColumn('related_goal_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da meta relacionada'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['employee_id'])
                ->addIndex(['given_by'])
                ->addIndex(['feedback_type'])
                ->addIndex(['related_review_id'])
                ->addIndex(['related_goal_id'])
                
                ->addForeignKey('employee_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('given_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('related_review_id', 'adms_performance_reviews', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('related_goal_id', 'adms_performance_goals', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}

