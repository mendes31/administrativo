<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDefaultGroupToAdmsRoomRequestTypes extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_room_request_types')) {
            return;
        }

        $table = $this->table('adms_room_request_types');

        if (!$table->hasColumn('default_responsible_group_id')) {
            $table->addColumn('default_responsible_group_id', 'integer', ['null' => true, 'after' => 'requires_responsible']);
        }

        // Mantemos default_responsible_user_id por compatibilidade, mas a preferência passa a ser o grupo.
        $table->update();
    }
}

