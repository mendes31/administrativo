<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterRoomsCalendarAndMeetingRsvpPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        // Grupo alinhado ao padrão do projeto.
        // ACL de páginas privadas do módulo deve nascer desautorizada (permission=0).
        $dash = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'AdminBookingDashboard' LIMIT 1");
        if (!$dash) {
            return;
        }
        $gid = (int) ($dash['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        $this->ensurePage(
            "SELECT id FROM adms_pages WHERE controller_url = 'rooms-calendar-integration-settings' LIMIT 1",
            [
                'name' => 'Integração calendário (Salas)',
                'controller' => 'RoomsCalendarIntegrationSettings',
                'controller_url' => 'rooms-calendar-integration-settings',
                'directory' => 'rooms',
                'obs' => 'Configuração futura de sincronização Outlook / Google Calendar para reservas.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            true,
            $now
        );

        $this->ensurePage(
            "SELECT id FROM adms_pages WHERE controller_url = 'meeting-booking-rsvp' LIMIT 1",
            [
                'name' => 'RSVP convite reunião (público)',
                'controller' => 'MeetingBookingRsvp',
                'controller_url' => 'meeting-booking-rsvp',
                'directory' => 'rooms',
                'obs' => 'Resposta a convite de participação em reserva (sem login).',
                'public_page' => 1,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            false,
            $now
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function ensurePage(string $existsSql, array $row, bool $initializePrivateAclAsZero, string $now): void
    {
        $exists = $this->fetchRow($existsSql);
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert($row)->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages') || !$initializePrivateAclAsZero) {
            return;
        }

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['rooms-calendar-integration-settings', 'meeting-booking-rsvp'] as $slug) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = '{$slug}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }
    }
}
