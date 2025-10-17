<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvMovementReasons extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_movement_reasons')) {
            $this->table('inv_movement_reasons')
                ->addColumn('type', 'enum', ['values' => ['entry', 'exit', 'transfer', 'adjust'], 'null' => false])
                ->addColumn('code', 'string', ['limit' => 30, 'null' => false])
                ->addColumn('description', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('active', 'boolean', ['default' => 1])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['type', 'code'], ['unique' => true])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_movement_reasons')) {
            $this->table('inv_movement_reasons')->drop()->save();
        }
    }
}









