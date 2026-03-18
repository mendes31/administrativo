<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsRoomRequestGroups extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_room_request_groups')) {
            $table = $this->table('adms_room_request_groups');
            $table
                ->addColumn('name', 'string', ['limit' => 150])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('is_active', 'boolean', ['default' => true])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['name'], ['unique' => true, 'name' => 'idx_room_req_groups_name'])
                ->create();
        }

        if (!$this->hasTable('adms_room_request_group_users')) {
            $link = $this->table('adms_room_request_group_users');
            $link
                ->addColumn('group_id', 'integer')
                ->addColumn('user_id', 'integer')
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['group_id', 'user_id'], ['unique' => true, 'name' => 'idx_room_req_group_users_unique'])
                ->addIndex(['group_id'], ['name' => 'idx_room_req_group_users_group'])
                ->addIndex(['user_id'], ['name' => 'idx_room_req_group_users_user'])
                ->create();
        }
    }
}

