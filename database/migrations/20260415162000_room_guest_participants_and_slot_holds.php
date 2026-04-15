<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RoomGuestParticipantsAndSlotHolds extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_booking_participants')) {
            $table = $this->table('adms_booking_participants');
            if ($table->hasForeignKey('user_id')) {
                $table->dropForeignKey('user_id')->save();
            }
            $table->changeColumn('user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])->save();
            if (!$table->hasColumn('guest_email')) {
                $table->addColumn('guest_email', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'user_id'])->save();
            }
            if (!$table->hasColumn('guest_name')) {
                $table->addColumn('guest_name', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'guest_email'])->save();
            }
            $table->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])->save();
        }

        if (!$this->hasTable('adms_room_booking_slot_holds')) {
            $this->table('adms_room_booking_slot_holds')
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('start_datetime', 'datetime', ['null' => false])
                ->addColumn('end_datetime', 'datetime', ['null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_display_name', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('hold_token', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('expires_at', 'datetime', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['room_id', 'expires_at'], ['name' => 'idx_room_holds_exp'])
                ->addIndex(['hold_token'], ['unique' => true, 'name' => 'uq_room_hold_token'])
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_room_booking_slot_holds')) {
            $this->table('adms_room_booking_slot_holds')->drop()->save();
        }
        if ($this->hasTable('adms_booking_participants')) {
            $t = $this->table('adms_booking_participants');
            if ($t->hasColumn('guest_email')) {
                $t->removeColumn('guest_email')->save();
            }
            if ($t->hasColumn('guest_name')) {
                $t->removeColumn('guest_name')->save();
            }
            if ($t->hasForeignKey('user_id')) {
                $t->dropForeignKey('user_id')->save();
            }
            $t->changeColumn('user_id', 'integer', ['signed' => false, 'null' => false])->save();
            $t->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])->save();
        }
    }
}
