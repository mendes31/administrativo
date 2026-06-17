<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Separa notificações de treinamentos em canais e-mail e in-app/push.
 * Não altera informativos, políticas, SAC nem demais módulos.
 */
final class SplitTrainingNotificationChannels extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_notification_settings')) {
            return;
        }

        $pairs = [
            'training_pending' => ['training_pending_email', 'training_pending_inapp'],
            'training_expiring' => ['training_expiring_email', 'training_expiring_inapp'],
            'training_expired' => ['training_expired_email', 'training_expired_inapp'],
            'training_new_mandatory' => ['training_new_mandatory_email', 'training_new_mandatory_inapp'],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($pairs as $oldKey => $newKeys) {
            $oldKeySql = str_replace("'", "''", $oldKey);
            $oldRow = $this->fetchRow(
                "SELECT enabled FROM adms_notification_settings WHERE setting_key = '{$oldKeySql}' LIMIT 1"
            );
            $wasEnabled = $oldRow && (int) ($oldRow['enabled'] ?? 0) === 1;

            foreach ($newKeys as $newKey) {
                $newKeySql = str_replace("'", "''", $newKey);
                $exists = $this->fetchRow(
                    "SELECT id FROM adms_notification_settings WHERE setting_key = '{$newKeySql}' LIMIT 1"
                );
                if ($exists) {
                    continue;
                }
                $this->table('adms_notification_settings')->insert([
                    'setting_key' => $newKey,
                    'enabled' => $wasEnabled ? 1 : 0,
                    'updated_at' => $now,
                ])->save();
            }

            if ($oldRow) {
                $this->execute(
                    "DELETE FROM adms_notification_settings WHERE setting_key = '{$oldKeySql}' LIMIT 1"
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_notification_settings')) {
            return;
        }

        $pairs = [
            'training_pending' => ['training_pending_email', 'training_pending_inapp'],
            'training_expiring' => ['training_expiring_email', 'training_expiring_inapp'],
            'training_expired' => ['training_expired_email', 'training_expired_inapp'],
            'training_new_mandatory' => ['training_new_mandatory_email', 'training_new_mandatory_inapp'],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($pairs as $oldKey => $newKeys) {
            $anyEnabled = false;
            foreach ($newKeys as $newKey) {
                $newKeySql = str_replace("'", "''", $newKey);
                $row = $this->fetchRow(
                    "SELECT enabled FROM adms_notification_settings WHERE setting_key = '{$newKeySql}' LIMIT 1"
                );
                if ($row && (int) ($row['enabled'] ?? 0) === 1) {
                    $anyEnabled = true;
                }
                $this->execute(
                    "DELETE FROM adms_notification_settings WHERE setting_key = '{$newKeySql}' LIMIT 1"
                );
            }

            $oldKeySql = str_replace("'", "''", $oldKey);
            $exists = $this->fetchRow(
                "SELECT id FROM adms_notification_settings WHERE setting_key = '{$oldKeySql}' LIMIT 1"
            );
            if (!$exists) {
                $this->table('adms_notification_settings')->insert([
                    'setting_key' => $oldKey,
                    'enabled' => $anyEnabled ? 1 : 0,
                    'updated_at' => $now,
                ])->save();
            }
        }
    }
}
