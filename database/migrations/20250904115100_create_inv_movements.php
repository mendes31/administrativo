<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvMovements extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_movements')) {
            $this->table('inv_movements')
                ->addColumn('type', 'enum', ['values' => ['entry', 'exit', 'transfer', 'adjust'], 'null' => false])
                ->addColumn('movement_date', 'datetime', ['null' => false])
                ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('from_stock_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('to_stock_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('from_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('to_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('reason_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('equipment_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('notes', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['movement_date'])
                ->addForeignKey('from_stock_id', 'inv_stocks', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('to_stock_id', 'inv_stocks', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('from_position_id', 'inv_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('to_position_id', 'inv_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_movements')) {
            $this->table('inv_movements')->drop()->save();
        }
    }
}









