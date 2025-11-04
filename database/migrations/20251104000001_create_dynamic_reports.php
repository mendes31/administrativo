<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDynamicReports extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_dynamic_reports')) {
            return;
        }

        $this->table('adms_dynamic_reports')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('is_public', 'boolean', ['default' => 0])
            ->addColumn('data_source', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('custom_sql', 'text', ['null' => true, 'comment' => 'SQL personalizado'])
            ->addColumn('query_mode', 'enum', ['values' => ['builder', 'custom_sql'], 'default' => 'builder'])
            ->addColumn('fields', 'text', ['null' => true, 'comment' => 'JSON'])
            ->addColumn('filters', 'text', ['null' => true, 'comment' => 'JSON'])
            ->addColumn('groupby', 'text', ['null' => true, 'comment' => 'JSON'])
            ->addColumn('orderby', 'text', ['null' => true, 'comment' => 'JSON'])
            ->addColumn('visualization_type', 'enum', [
                'values' => ['table', 'bar_chart', 'line_chart', 'pie_chart', 'donut_chart', 'area_chart', 'column_chart'],
                'default' => 'table'
            ])
            ->addColumn('chart_config', 'text', ['null' => true, 'comment' => 'JSON'])
            ->addColumn('refresh_interval', 'integer', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('is_active', 'boolean', ['default' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['created_by'])
            ->addIndex(['data_source'])
            ->addIndex(['category'])
            ->create();

        if (!$this->hasTable('adms_report_favorites')) {
            $this->table('adms_report_favorites')
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('report_id', 'adms_dynamic_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['user_id', 'report_id'], ['unique' => true])
                ->create();
        }

        if (!$this->hasTable('adms_report_executions')) {
            $this->table('adms_report_executions')
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('execution_time', 'decimal', ['precision' => 10, 'scale' => 4, 'null' => true])
                ->addColumn('rows_returned', 'integer', ['null' => true])
                ->addColumn('executed_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('report_id', 'adms_dynamic_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['report_id'])
                ->addIndex(['executed_at'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_report_executions')) {
            $this->table('adms_report_executions')->drop()->save();
        }
        if ($this->hasTable('adms_report_favorites')) {
            $this->table('adms_report_favorites')->drop()->save();
        }
        if ($this->hasTable('adms_dynamic_reports')) {
            $this->table('adms_dynamic_reports')->drop()->save();
        }
    }
}

