<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Vincula entrada da lista de espera à reserva quando o usuário ganha a vaga.
 */
final class AddBookingIdToBookingWaitlist extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_booking_waitlist') || !$this->hasTable('adms_room_bookings')) {
            return;
        }

        $table = $this->table('adms_booking_waitlist');

        if (!$table->hasColumn('booking_id')) {
            $table
                ->addColumn('booking_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'user_id',
                    'comment' => 'Reserva criada quando o usuário obteve a vaga',
                ])
                ->addIndex(['booking_id'], ['name' => 'idx_booking_waitlist_booking_id'])
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->update();
        }
    }
}
