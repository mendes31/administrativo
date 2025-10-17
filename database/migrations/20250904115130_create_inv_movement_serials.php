<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvMovementSerials extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_movement_serials')) {
            $this->table('inv_movement_serials')
                ->addColumn('inv_movement_item_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('inv_item_serial_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('created_at', 'timestamp')
                ->addIndex(['inv_movement_item_id'])
                ->addForeignKey('inv_movement_item_id', 'inv_movement_items', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('inv_item_serial_id', 'inv_item_serials', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_movement_serials')) {
            $this->table('inv_movement_serials')->drop()->save();
        }
    }
}









