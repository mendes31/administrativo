<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsStrategicPlanObservations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_strategic_plan_observations')) {
            $table = $this->table('adms_strategic_plan_observations');
            $table
                ->addColumn('strategic_plan_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('observation', 'text', ['null' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('strategic_plan_id', 'adms_strategic_plans', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['strategic_plan_id', 'created_at'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_strategic_plan_observations')) {
            $this->table('adms_strategic_plan_observations')->drop()->save();
        }
    }
}
