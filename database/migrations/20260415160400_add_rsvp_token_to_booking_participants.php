<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRsvpTokenToBookingParticipants extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_booking_participants')) {
            return;
        }

        $table = $this->table('adms_booking_participants');
        if ($table->hasColumn('rsvp_token')) {
            return;
        }

        $table
            ->addColumn('rsvp_token', 'string', ['limit' => 64, 'null' => true, 'default' => null, 'after' => 'notified_at'])
            ->addColumn('rsvp_responded_at', 'datetime', ['null' => true, 'default' => null, 'after' => 'rsvp_token'])
            ->addIndex(['rsvp_token'], ['unique' => true, 'name' => 'uq_bp_rsvp_token'])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_booking_participants')) {
            return;
        }
        try {
            $this->execute('ALTER TABLE adms_booking_participants DROP INDEX uq_bp_rsvp_token');
        } catch (\Throwable) {
            // índice inexistente
        }
        $table = $this->table('adms_booking_participants');
        $changed = false;
        if ($table->hasColumn('rsvp_responded_at')) {
            $table->removeColumn('rsvp_responded_at');
            $changed = true;
        }
        if ($table->hasColumn('rsvp_token')) {
            $table->removeColumn('rsvp_token');
            $changed = true;
        }
        if ($changed) {
            $table->update();
        }
    }
}
