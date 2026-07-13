<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Eventos de RH relevantes para LNT (novo colaborador, cargo, desligamento, etc.).
 */
final class CreateTrainingLntEvents extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_lnt_events')) {
            $this->table('adms_training_lnt_events')
                ->addColumn('event_type', 'string', ['limit' => 40, 'null' => false])
                ->addColumn('action_label', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('position_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('collaborator_name', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('collaborator_cpf', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('department_name', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('position_name', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('data_admissao', 'date', ['null' => true])
                ->addColumn('data_desligamento', 'date', ['null' => true])
                ->addColumn('details_json', 'text', ['null' => true])
                ->addColumn('actor_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('inapp_notified_at', 'datetime', ['null' => true])
                ->addColumn('email_sent_at', 'datetime', ['null' => true])
                ->addColumn('digest_sent_at', 'datetime', ['null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['event_type'])
                ->addIndex(['created_at'])
                ->addIndex(['user_id'])
                ->addIndex(['digest_sent_at'])
                ->create();
        }

        if ($this->hasTable('adms_notification_settings')) {
            $now = date('Y-m-d H:i:s');
            foreach ([
                'training_lnt_event_inapp' => 1,
                'training_lnt_event_digest_email' => 1,
            ] as $key => $enabled) {
                $keySql = str_replace("'", "''", $key);
                $exists = $this->fetchRow(
                    "SELECT id FROM adms_notification_settings WHERE setting_key = '{$keySql}' LIMIT 1"
                );
                if (!$exists) {
                    $this->table('adms_notification_settings')->insert([
                        'setting_key' => $key,
                        'enabled' => $enabled,
                        'updated_at' => $now,
                    ])->save();
                }
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_notification_settings')) {
            $this->execute(
                "DELETE FROM adms_notification_settings
                 WHERE setting_key IN ('training_lnt_event_inapp', 'training_lnt_event_digest_email')"
            );
        }

        if ($this->hasTable('adms_training_lnt_events')) {
            $this->table('adms_training_lnt_events')->drop()->save();
        }
    }
}
