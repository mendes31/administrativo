<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regista a rota list-bookings (ListBookings) em adms_pages.
 * Página privada deve nascer desautorizada (permission=0).
 */
final class RegisterListBookingsPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListBookings' OR controller_url = 'list-bookings' LIMIT 1");
        if ($exists) {
            return;
        }

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ViewBooking' LIMIT 1");
        if (!$ref) {
            return;
        }

        $gid = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'Listar Minhas Reservas (Salas)',
            'controller' => 'ListBookings',
            'controller_url' => 'list-bookings',
            'directory' => 'rooms',
            'obs' => 'Lista reservas do utilizador (ou todas para administrador).',
            'public_page' => 0,
            'default_page' => 1,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int)($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
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
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListBookings' LIMIT 1");
        if (!$row) {
            return;
        }
        $pid = (int)$row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
    }
}
