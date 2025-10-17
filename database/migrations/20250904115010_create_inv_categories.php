<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvCategories extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_categories')) {
            $this->table('inv_categories')
                ->addColumn('name', 'string', ['limit' => 80, 'null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['name'], ['unique' => true])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_categories')) {
            $this->table('inv_categories')->drop()->save();
        }
    }
}









