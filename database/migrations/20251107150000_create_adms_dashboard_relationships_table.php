<?php

use Phinx\Migration\AbstractMigration;

/**
 * Tabela de relacionamentos entre relatórios vinculados a um dashboard
 */
class CreateAdmsDashboardRelationshipsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_dashboard_relationships');

        $table
            ->addColumn('dashboard_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('primary_report_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('primary_field', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('foreign_report_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('foreign_field', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('relationship_type', 'string', ['limit' => 20, 'default' => 'one_to_many', 'null' => false])
            ->addColumn('filter_direction', 'string', ['limit' => 20, 'default' => 'bidirectional', 'null' => false])
            ->addColumn('join_type', 'string', ['limit' => 20, 'default' => 'inner', 'null' => false])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['dashboard_id'])
            ->addIndex(['primary_report_id'])
            ->addIndex(['foreign_report_id'])
            ->addForeignKey('dashboard_id', 'adms_dashboards', 'id', ['constraint' => 'fk_dashboard_rel_dashboard', 'delete' => 'CASCADE'])
            ->addForeignKey('primary_report_id', 'adms_dynamic_reports', 'id', ['constraint' => 'fk_dashboard_rel_primary_report', 'delete' => 'CASCADE'])
            ->addForeignKey('foreign_report_id', 'adms_dynamic_reports', 'id', ['constraint' => 'fk_dashboard_rel_foreign_report', 'delete' => 'CASCADE'])
            ->create();
    }
}

