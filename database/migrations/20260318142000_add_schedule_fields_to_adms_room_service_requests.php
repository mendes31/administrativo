<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddScheduleFieldsToAdmsRoomServiceRequests extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_room_service_requests')) {
            return;
        }

        $table = $this->table('adms_room_service_requests');

        // Campos de agendamento da solicitação
        if (!$table->hasColumn('service_date')) {
            $table->addColumn('service_date', 'date', ['null' => false, 'after' => 'status']);
        }

        if (!$table->hasColumn('start_time')) {
            $table->addColumn('start_time', 'time', ['null' => false, 'after' => 'service_date']);
        }

        if (!$table->hasColumn('end_time')) {
            $table->addColumn('end_time', 'time', ['null' => true, 'after' => 'start_time']);
        }

        if (!$table->hasColumn('location')) {
            $table->addColumn('location', 'string', ['limit' => 255, 'null' => false, 'default' => '', 'after' => 'end_time']);
        }

        if (!$table->hasColumn('priority')) {
            $table->addColumn('priority', 'string', ['limit' => 20, 'null' => false, 'default' => 'normal', 'after' => 'location']);
        }

        // Índices úteis
        $table
            ->addIndex(['service_date'])
            ->addIndex(['priority'])
            ->update();
    }
}

