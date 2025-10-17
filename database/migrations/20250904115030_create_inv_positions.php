<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvPositions extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_positions')) {
            $this->table('inv_positions')
                ->addColumn('inv_stock_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('code', 'string', ['limit' => 30, 'null' => false])
                ->addColumn('description', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['inv_stock_id', 'code'], ['unique' => true])
                ->addForeignKey('inv_stock_id', 'inv_stocks', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_positions')) {
            $this->table('inv_positions')->drop()->save();
        }
    }
}









