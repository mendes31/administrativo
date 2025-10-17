<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvStocks extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_stocks')) {
            $this->table('inv_stocks')
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('code', 'string', ['limit' => 10, 'null' => false])
                ->addColumn('adms_branch_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('active', 'boolean', ['default' => 1, 'null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->addIndex(['code'], ['unique' => true])
                ->addForeignKey('adms_branch_id', 'adms_branches', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('inv_stocks')) {
            $this->table('inv_stocks')->drop()->save();
        }
    }
}









