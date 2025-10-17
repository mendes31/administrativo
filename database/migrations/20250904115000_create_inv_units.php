<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvUnits extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_units')) {
            $this->table('inv_units')
                ->addColumn('code', 'string', ['limit' => 10, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 60, 'null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['code'], ['unique' => true])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_units')) {
            $this->table('inv_units')->drop()->save();
        }
    }
}




