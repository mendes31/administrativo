<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsRoomServiceRequests extends AbstractMigration
{
    public function change(): void
    {
        // Em produção, a tabela ainda não existe. Em dev, pode já existir de uma tentativa anterior.
        if (!$this->hasTable('adms_room_service_requests')) {
            $table = $this->table('adms_room_service_requests');

            $table
                ->addColumn('requester_user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('request_type_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('request_description', 'text', ['null' => true])
                ->addColumn('quantity', 'integer', ['null' => true])
                ->addColumn('status', 'string', ['limit' => 30, 'null' => false, 'default' => 'pending'])
                ->addColumn('responsible_group_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('claimed_by_user_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('claimed_at', 'datetime', ['null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['status'])
                ->addIndex(['requester_user_id'])
                ->addIndex(['request_type_id'])
                ->addIndex(['responsible_group_id'])
                ->addIndex(['claimed_by_user_id'])
                ->addForeignKey('requester_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('request_type_id', 'adms_room_request_types', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('responsible_group_id', 'adms_room_request_groups', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('claimed_by_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        } else {
            // Se a tabela já existir (caso de dev), garantir que os tipos das colunas estão compatíveis
            // com as FKs e então adicionar/garantir os FKs.
            $table = $this->table('adms_room_service_requests');

            $table
                ->changeColumn('requester_user_id', 'integer', ['signed' => false, 'null' => false])
                ->changeColumn('request_type_id', 'integer', ['signed' => false, 'null' => false])
                ->changeColumn('responsible_group_id', 'integer', ['signed' => false, 'null' => true])
                ->changeColumn('claimed_by_user_id', 'integer', ['signed' => false, 'null' => true])
                ->addForeignKey('requester_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('request_type_id', 'adms_room_request_types', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('responsible_group_id', 'adms_room_request_groups', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('claimed_by_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->update();
        }
    }
}

