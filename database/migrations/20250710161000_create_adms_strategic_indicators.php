<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsStrategicIndicators extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_strategic_indicators')) {
            $table = $this->table('adms_strategic_indicators');
            $table
                ->addColumn('strategic_plan_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('target_value', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
                ->addColumn('current_value', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
                ->addColumn('unit', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('frequency', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('responsible_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'active'])
                ->addColumn('created_by', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('responsible_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_strategic_indicators')->drop()->save();
    }
} 