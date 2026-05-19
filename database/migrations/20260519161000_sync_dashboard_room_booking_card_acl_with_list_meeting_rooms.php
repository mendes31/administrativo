<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante que o card "Reserva de Salas" permanece com default fechado e ACL a zero nos níveis.
 * A liberação é manual em Níveis de Acesso (Card Dashboard - Reserva de Salas).
 */
final class SyncDashboardRoomBookingCardAclWithListMeetingRooms extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_pages SET default_page = 0, updated_at = NOW()
             WHERE controller = 'DashboardCardRoomBooking'"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0, alp.updated_at = NOW()
             WHERE p.controller = 'DashboardCardRoomBooking'"
        );
    }

    public function down(): void
    {
    }
}
