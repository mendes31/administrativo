<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsPdiTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela principal de PDIs
        if (!$this->hasTable('adms_pdi_plans')) {
            $table = $this->table('adms_pdi_plans', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do colaborador'])
                ->addColumn('manager_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID do gestor responsável'])
                ->addColumn('title', 'string', ['limit' => 255, 'comment' => 'Título do PDI'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do plano'])
                ->addColumn('period_start', 'date', ['null' => false, 'comment' => 'Data de início do período'])
                ->addColumn('period_end', 'date', ['null' => false, 'comment' => 'Data de término do período'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft', 'comment' => 'Status: draft, active, completed, cancelled'])
                ->addColumn('current_level', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Nível atual do colaborador'])
                ->addColumn('target_level', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Nível alvo/objetivo'])
                ->addColumn('career_goal', 'text', ['null' => true, 'comment' => 'Objetivo de carreira'])
                ->addColumn('evaluation_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da avaliação relacionada'])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que criou'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('approved_at', 'datetime', ['null' => true, 'comment' => 'Data de aprovação'])
                ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID do usuário que aprovou'])
                
                ->addIndex(['user_id'])
                ->addIndex(['manager_id'])
                ->addIndex(['status'])
                ->addIndex(['period_start', 'period_end'])
                ->addIndex(['evaluation_id'])
                
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('manager_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('approved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de ações/atividades do PDI
        if (!$this->hasTable('adms_pdi_actions')) {
            $table = $this->table('adms_pdi_actions', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('pdi_plan_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do plano PDI'])
                ->addColumn('title', 'string', ['limit' => 255, 'comment' => 'Título da ação'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição detalhada'])
                ->addColumn('action_type', 'string', ['limit' => 50, 'comment' => 'Tipo: training, course, mentoring, project, reading, other'])
                ->addColumn('category', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria: technical, behavioral, leadership, etc'])
                ->addColumn('priority', 'string', ['limit' => 20, 'default' => 'medium', 'comment' => 'Prioridade: low, medium, high'])
                ->addColumn('start_date', 'date', ['null' => true, 'comment' => 'Data de início'])
                ->addColumn('end_date', 'date', ['null' => true, 'comment' => 'Data de término'])
                ->addColumn('expected_hours', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Horas esperadas'])
                ->addColumn('actual_hours', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'Horas realizadas'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, in_progress, completed, cancelled'])
                ->addColumn('progress_percentage', 'integer', ['default' => 0, 'comment' => 'Percentual de conclusão (0-100)'])
                ->addColumn('training_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID do treinamento relacionado'])
                ->addColumn('resource_url', 'string', ['limit' => 500, 'null' => true, 'comment' => 'URL de recurso externo'])
                ->addColumn('notes', 'text', ['null' => true, 'comment' => 'Observações'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('completed_at', 'datetime', ['null' => true, 'comment' => 'Data de conclusão'])
                
                ->addIndex(['pdi_plan_id'])
                ->addIndex(['status'])
                ->addIndex(['action_type'])
                ->addIndex(['priority'])
                ->addIndex(['training_id'])
                
                ->addForeignKey('pdi_plan_id', 'adms_pdi_plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de competências do PDI
        if (!$this->hasTable('adms_pdi_competencies')) {
            $table = $this->table('adms_pdi_competencies', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('pdi_plan_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do plano PDI'])
                ->addColumn('competency_name', 'string', ['limit' => 255, 'comment' => 'Nome da competência'])
                ->addColumn('competency_type', 'string', ['limit' => 50, 'comment' => 'Tipo: technical, behavioral, leadership'])
                ->addColumn('current_level', 'integer', ['default' => 1, 'comment' => 'Nível atual (1-5)'])
                ->addColumn('target_level', 'integer', ['default' => 3, 'comment' => 'Nível alvo (1-5)'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['pdi_plan_id'])
                ->addIndex(['competency_type'])
                
                ->addForeignKey('pdi_plan_id', 'adms_pdi_plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de feedbacks e acompanhamentos
        if (!$this->hasTable('adms_pdi_feedbacks')) {
            $table = $this->table('adms_pdi_feedbacks', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('pdi_plan_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do plano PDI'])
                ->addColumn('pdi_action_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da ação específica (opcional)'])
                ->addColumn('feedback_type', 'string', ['limit' => 50, 'default' => 'general', 'comment' => 'Tipo: general, action, milestone, final'])
                ->addColumn('feedback_text', 'text', ['null' => false, 'comment' => 'Texto do feedback'])
                ->addColumn('given_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID de quem deu o feedback'])
                ->addColumn('given_to', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID de quem recebeu o feedback'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['pdi_plan_id'])
                ->addIndex(['pdi_action_id'])
                ->addIndex(['given_by'])
                ->addIndex(['given_to'])
                ->addIndex(['feedback_type'])
                
                ->addForeignKey('pdi_plan_id', 'adms_pdi_plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('pdi_action_id', 'adms_pdi_actions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('given_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('given_to', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de metas do PDI
        if (!$this->hasTable('adms_pdi_goals')) {
            $table = $this->table('adms_pdi_goals', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('pdi_plan_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do plano PDI'])
                ->addColumn('goal_title', 'string', ['limit' => 255, 'comment' => 'Título da meta'])
                ->addColumn('goal_description', 'text', ['null' => true, 'comment' => 'Descrição da meta'])
                ->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Valor alvo'])
                ->addColumn('current_value', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0, 'comment' => 'Valor atual'])
                ->addColumn('unit', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Unidade de medida'])
                ->addColumn('deadline', 'date', ['null' => true, 'comment' => 'Prazo'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, in_progress, achieved, failed'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('achieved_at', 'datetime', ['null' => true, 'comment' => 'Data de alcance'])
                
                ->addIndex(['pdi_plan_id'])
                ->addIndex(['status'])
                ->addIndex(['deadline'])
                
                ->addForeignKey('pdi_plan_id', 'adms_pdi_plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}

