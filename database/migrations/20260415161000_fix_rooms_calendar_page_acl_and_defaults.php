<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alinha permissões do módulo Reserva de Salas ao padrão do projeto:
 * - páginas privadas devem nascer desautorizadas (permission=0);
 * - default_page = 0 (evita permissão implícita em novos níveis; RSVP continua público via public_page).
 */
final class FixRoomsCalendarPageAclAndDefaults extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages SET default_page = 0
             WHERE controller IN ('RoomsCalendarIntegrationSettings', 'MeetingBookingRsvp')"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $cal = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'RoomsCalendarIntegrationSettings' LIMIT 1");
        if (!$cal) {
            return;
        }

        $targetId = (int) $cal['id'];
        if ($targetId <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$targetId}");
        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$targetId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
    }

    public function down(): void
    {
        // Irreversível sem snapshot do ACL anterior; mantém estado atual.
    }
}
