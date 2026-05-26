<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacCategories extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_categories')) {
            return;
        }

        $table = $this->table('sac_categories');

        $table->addColumn('name', 'string', ['limit' => 100])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('color', 'string', ['limit' => 7, 'null' => true])
              ->addColumn('icon', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('default_sla_response_hours', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('default_sla_resolution_hours', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('is_active', 'boolean', ['default' => 1])
              ->addColumn('display_order', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['name'], ['unique' => true])
              ->addIndex(['is_active'])

              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
