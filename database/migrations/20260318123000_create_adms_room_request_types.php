<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsRoomRequestTypes extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_room_request_types')) {
            return;
        }

        $table = $this->table('adms_room_request_types');

        $table
            ->addColumn('code', 'string', ['limit' => 50])
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('requires_responsible', 'boolean', ['default' => true])
            ->addColumn('default_responsible_user_id', 'integer', ['null' => true])
            ->addColumn('requires_quantity', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['code'], ['unique' => true, 'name' => 'idx_adms_room_req_types_code'])
            ->create();
    }
}

