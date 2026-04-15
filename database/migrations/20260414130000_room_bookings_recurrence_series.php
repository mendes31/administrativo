<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Série de recorrência: várias linhas em adms_room_bookings partilham o mesmo recurrence_series_id.
 */
final class RoomBookingsRecurrenceSeries extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_room_bookings')) {
            return;
        }
        $t = $this->table('adms_room_bookings');
        if (!$t->hasColumn('recurrence_series_id')) {
            $t->addColumn('recurrence_series_id', 'string', [
                'limit' => 36,
                'null' => true,
                'comment' => 'UUID da série (recorrência semanal); NULL = reserva avulsa',
            ]);
            $t->addIndex(['recurrence_series_id'], ['name' => 'idx_room_bookings_recurrence_series']);
            $t->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_room_bookings')) {
            return;
        }
        $t = $this->table('adms_room_bookings');
        if ($t->hasColumn('recurrence_series_id')) {
            $t->removeIndexByName('idx_room_bookings_recurrence_series');
            $t->removeColumn('recurrence_series_id');
            $t->update();
        }
    }
}
