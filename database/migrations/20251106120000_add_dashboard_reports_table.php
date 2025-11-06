<?php

use Phinx\Migration\AbstractMigration;

/**
 * Criar tabela de relacionamento para múltiplos relatórios por dashboard
 */
class AddDashboardReportsTable extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de relacionamento N:N entre dashboards e relatórios
        $table = $this->table('adms_dashboard_reports', ['id' => false, 'primary_key' => ['dashboard_id', 'report_id']]);
        $table
            ->addColumn('dashboard_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('is_primary', 'boolean', ['default' => false, 'null' => false, 'comment' => 'Se é o relatório principal'])
            ->addColumn('display_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addForeignKey('dashboard_id', 'adms_dashboards', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('report_id', 'adms_dynamic_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['dashboard_id'])
            ->addIndex(['report_id'])
            ->create();
        
        // Migrar dados existentes para a nova estrutura
        $this->execute("
            INSERT INTO adms_dashboard_reports (dashboard_id, report_id, is_primary, display_order)
            SELECT id, dynamic_report_id, 1, 1
            FROM adms_dashboards
            WHERE dynamic_report_id IS NOT NULL
        ");
    }
}

