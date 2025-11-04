<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateKpiDashboards extends AbstractMigration
{
    public function up(): void
    {
        // Tabela de Dashboards de KPI
        if (!$this->hasTable('adms_kpi_dashboards')) {
            $table = $this->table('adms_kpi_dashboards');
            $table->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('layout', 'enum', ['values' => ['grid', 'flex', 'custom'], 'default' => 'grid'])
                ->addColumn('refresh_interval', 'integer', ['null' => true, 'comment' => 'Intervalo de atualização em segundos'])
                ->addColumn('is_public', 'boolean', ['default' => false])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['created_by'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        // Tabela de Widgets de KPI
        if (!$this->hasTable('adms_kpi_widgets')) {
            $table = $this->table('adms_kpi_widgets');
            $table->addColumn('dashboard_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'Relatório dinâmico vinculado'])
                ->addColumn('title', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('widget_type', 'enum', ['values' => ['number', 'chart_bar', 'chart_line', 'chart_pie', 'chart_doughnut', 'table', 'gauge'], 'default' => 'number'])
                ->addColumn('size', 'enum', ['values' => ['small', 'medium', 'large', 'full'], 'default' => 'medium'])
                ->addColumn('position_order', 'integer', ['default' => 0])
                ->addColumn('color_scheme', 'string', ['limit' => 50, 'null' => true, 'comment' => 'primary, success, danger, warning, info'])
                ->addColumn('icon', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Ícone FontAwesome'])
                ->addColumn('value_format', 'string', ['limit' => 50, 'null' => true, 'comment' => 'number, currency, percentage, text'])
                ->addColumn('value_prefix', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('value_suffix', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('target_value', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'comment' => 'Meta/valor alvo'])
                ->addColumn('config_json', 'text', ['null' => true, 'comment' => 'Configurações adicionais em JSON'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['dashboard_id'])
                ->addIndex(['report_id'])
                ->addIndex(['position_order'])
                ->addForeignKey('dashboard_id', 'adms_kpi_dashboards', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('report_id', 'adms_dynamic_reports', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        // Tabela de permissões de dashboard (quem pode ver)
        if (!$this->hasTable('adms_kpi_dashboard_permissions')) {
            $table = $this->table('adms_kpi_dashboard_permissions');
            $table->addColumn('dashboard_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('access_level_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['dashboard_id'])
                ->addIndex(['user_id'])
                ->addIndex(['access_level_id'])
                ->addForeignKey('dashboard_id', 'adms_kpi_dashboards', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('access_level_id', 'adms_access_levels', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_kpi_dashboard_permissions')) {
            $this->table('adms_kpi_dashboard_permissions')->drop()->save();
        }
        
        if ($this->hasTable('adms_kpi_widgets')) {
            $this->table('adms_kpi_widgets')->drop()->save();
        }
        
        if ($this->hasTable('adms_kpi_dashboards')) {
            $this->table('adms_kpi_dashboards')->drop()->save();
        }
    }
}

