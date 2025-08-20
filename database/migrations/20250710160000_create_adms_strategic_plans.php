<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsStrategicPlans extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_strategic_plans')) {
            $table = $this->table('adms_strategic_plans');
            $table
                ->addColumn('department_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('responsible_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('what', 'text', ['null' => true])
                ->addColumn('why', 'text', ['null' => true])
                ->addColumn('where_field', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('who_field', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('start_date', 'date', ['null' => true])
                ->addColumn('end_date', 'date', ['null' => true])
                ->addColumn('how', 'text', ['null' => true])
                ->addColumn('how_much', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true])
                ->addColumn('completed', 'boolean', ['default' => false])
                ->addColumn('status', 'enum', ['values' => ['Não iniciado', 'Em andamento', 'Concluído', 'Atrasado'], 'default' => 'Não iniciado'])
                ->addColumn('comment', 'text', ['null' => true])
                ->addColumn('direction_comment', 'text', ['null' => true])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('department_id', 'adms_departments', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('responsible_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_strategic_plans')->drop()->save();
    }
} 