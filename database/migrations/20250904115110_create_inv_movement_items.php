<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvMovementItems extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_movement_items')) {
            $this->table('inv_movement_items')
                ->addColumn('inv_movement_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('inv_item_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('qty', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false])
                ->addColumn('unit_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('total_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'null' => true])
                ->addColumn('batch_code', 'string', ['limit' => 60, 'null' => true])
                ->addColumn('expiration_date', 'date', ['null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['inv_movement_id'])
                ->addForeignKey('inv_movement_id', 'inv_movements', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_item_id', 'inv_items', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_movement_items')) {
            $this->table('inv_movement_items')->drop()->save();
        }
    }
}









