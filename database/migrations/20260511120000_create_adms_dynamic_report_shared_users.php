<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsDynamicReportSharedUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_dynamic_report_shared_users')) {
            return;
        }

        $this->table('adms_dynamic_report_shared_users')
            ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('report_id', 'adms_dynamic_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['report_id', 'user_id'], ['unique' => true])
            ->addIndex(['user_id'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_dynamic_report_shared_users')) {
            $this->table('adms_dynamic_report_shared_users')->drop()->save();
        }
    }
}
