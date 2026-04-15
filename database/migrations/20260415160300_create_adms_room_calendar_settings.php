<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configuração de integração Outlook/Google (Reserva de Salas) — editável na aplicação, não no .env.
 */
final class CreateAdmsRoomCalendarSettings extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_room_calendar_settings')) {
            return;
        }

        $this->table('adms_room_calendar_settings', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['signed' => false, 'limit' => 10, 'null' => false, 'default' => 1])
            ->addColumn('outlook_sync_enabled', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('google_sync_enabled', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('outlook_tenant_id', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('outlook_client_id', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('google_client_id', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->create();

        $this->execute(
            "INSERT INTO adms_room_calendar_settings (id, outlook_sync_enabled, google_sync_enabled, outlook_tenant_id, outlook_client_id, google_client_id, updated_at, updated_by)
             VALUES (1, 0, 0, NULL, NULL, NULL, NOW(), NULL)"
        );
    }

    public function down(): void
    {
        if ($this->hasTable('adms_room_calendar_settings')) {
            $this->table('adms_room_calendar_settings')->drop()->save();
        }
    }
}
