<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Compromissos pessoais no calendário do utilizador (perfil).
 */
final class AdmsUserCalendarEntries extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_user_calendar_entries')) {
            return;
        }

        $this->table('adms_user_calendar_entries', ['id' => 'id', 'primary_key' => ['id']])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('start_datetime', 'datetime', ['null' => false])
            ->addColumn('end_datetime', 'datetime', ['null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id', 'start_datetime'], ['name' => 'idx_user_cal_user_start'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_user_calendar_entries')) {
            $this->table('adms_user_calendar_entries')->drop()->save();
        }
    }
}
