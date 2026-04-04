<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Vincula solicitações de serviço (lanches, equipamentos…) a uma reserva opcionalmente.
 * NULL = solicitação avulsa (comportamento anterior).
 */
final class AddBookingIdToRoomServiceRequests extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_room_service_requests') || !$this->hasTable('adms_room_bookings')) {
            return;
        }

        $table = $this->table('adms_room_service_requests');

        if (!$table->hasColumn('booking_id')) {
            $table
                ->addColumn('booking_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'requester_user_id',
                    'comment' => 'Reserva vinculada (opcional)',
                ])
                ->addIndex(['booking_id'], ['name' => 'idx_room_srv_req_booking_id'])
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->update();
        }
    }
}
