<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvItemSerials extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_serials')) {
            $this->table('inv_item_serials')
                ->addColumn('inv_item_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('serial_code', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('current_stock_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('current_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('status', 'enum', ['values' => ['available', 'moved', 'consumed', 'adjusted'], 'default' => 'available'])
                ->addColumn('acquisition_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['serial_code'], ['unique' => true])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('current_stock_id', 'inv_stocks', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('current_position_id', 'inv_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_item_serials')) {
            $this->table('inv_item_serials')->drop()->save();
        }
    }
}









