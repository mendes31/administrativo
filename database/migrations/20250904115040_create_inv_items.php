<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvItems extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_items')) {
            $this->table('inv_items')
                ->addColumn('code', 'string', ['limit' => 40, 'null' => false])
                ->addColumn('description', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('inv_unit_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('inv_category_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('admin_type', 'enum', ['values' => ['none', 'serial', 'lot'], 'default' => 'none', 'null' => false])
                ->addColumn('average_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('last_cost', 'decimal', ['precision' => 15, 'scale' => 6, 'default' => 0])
                ->addColumn('min_stock', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0])
                ->addColumn('max_stock', 'decimal', ['precision' => 15, 'scale' => 4, 'default' => 0])
                ->addColumn('active', 'boolean', ['default' => 1])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['code'], ['unique' => true])
                ->addForeignKey('inv_unit_id', 'inv_units', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('inv_category_id', 'inv_categories', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_items')) {
            $this->table('inv_items')->drop()->save();
        }
    }
}









