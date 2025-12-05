<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePeopleAnalyticsTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Definição de KPIs de RH
        if (!$this->hasTable('adms_people_kpis')) {
            $table = $this->table('adms_people_kpis', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('kpi_code', 'string', ['limit' => 50, 'comment' => 'Código do KPI'])
                ->addColumn('kpi_name', 'string', ['limit' => 255, 'comment' => 'Nome do KPI'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição'])
                ->addColumn('kpi_category', 'string', ['limit' => 50, 'comment' => 'Categoria: turnover, absenteeism, engagement, performance, training'])
                ->addColumn('calculation_type', 'string', ['limit' => 50, 'comment' => 'Tipo: count, percentage, average, sum'])
                ->addColumn('formula', 'text', ['null' => true, 'comment' => 'Fórmula de cálculo (SQL ou expressão)'])
                ->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Valor alvo'])
                ->addColumn('unit', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Unidade de medida'])
                ->addColumn('period_type', 'string', ['limit' => 20, 'default' => 'monthly', 'comment' => 'Período: daily, weekly, monthly, quarterly, yearly'])
                ->addColumn('status', 'boolean', ['default' => true, 'comment' => 'Ativo'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['kpi_code'], ['unique' => true])
                ->addIndex(['kpi_category'])
                ->addIndex(['status'])
                
                ->create();
        }
        
        // Tabela de Resultados de KPIs (histórico)
        if (!$this->hasTable('adms_people_kpi_results')) {
            $table = $this->table('adms_people_kpi_results', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('kpi_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do KPI'])
                ->addColumn('period_start', 'date', ['null' => false, 'comment' => 'Início do período'])
                ->addColumn('period_end', 'date', ['null' => false, 'comment' => 'Fim do período'])
                ->addColumn('calculated_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'comment' => 'Valor calculado'])
                ->addColumn('target_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Valor alvo'])
                ->addColumn('variance', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'comment' => 'Variação'])
                ->addColumn('variance_percentage', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'comment' => 'Variação percentual'])
                ->addColumn('filter_department_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'Filtro por departamento'])
                ->addColumn('filter_position_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'Filtro por cargo'])
                ->addColumn('calculated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'comment' => 'Data do cálculo'])
                
                ->addIndex(['kpi_id'])
                ->addIndex(['period_start', 'period_end'])
                ->addIndex(['filter_department_id'])
                ->addIndex(['filter_position_id'])
                ->addIndex(['calculated_at'])
                
                ->addForeignKey('kpi_id', 'adms_people_kpis', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('filter_department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('filter_position_id', 'adms_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Filtros de Analytics (salvar filtros personalizados)
        if (!$this->hasTable('adms_people_analytics_filters')) {
            $table = $this->table('adms_people_analytics_filters', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('filter_name', 'string', ['limit' => 255, 'comment' => 'Nome do filtro'])
                ->addColumn('filter_config', 'text', ['null' => false, 'comment' => 'JSON com configuração do filtro'])
                ->addColumn('is_default', 'boolean', ['default' => false, 'comment' => 'Filtro padrão'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['user_id'])
                ->addIndex(['is_default'])
                
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}

