<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvBalances extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_balances')) {
            $this->table('inv_balances')
                ->addColumn('inv_item_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('inv_stock_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('inv_position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('batch_code', 'string', ['limit' => 60, 'null' => true])
                ->addColumn('expiration_date', 'date', ['null' => true])
                ->addColumn('qty', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0])
                ->addColumn('average_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['inv_item_id', 'inv_stock_id', 'inv_position_id', 'batch_code', 'expiration_date'], ['unique' => true, 'name' => 'idx_balance_unique'])
                ->addIndex(['inv_item_id'])
                ->addIndex(['inv_stock_id'])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_stock_id', 'inv_stocks', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_position_id', 'inv_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_balances')) {
            $this->table('inv_balances')->drop()->save();
        }
    }
}









