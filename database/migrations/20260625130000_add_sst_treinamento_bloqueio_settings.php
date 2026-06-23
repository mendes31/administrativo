<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configurações de bloqueio operacional EPI × treinamento SST.
 */
final class AddSstTreinamentoBloqueioSettings extends AbstractMigration
{
    /** @var list<string> */
    private const KEYS = [
        'sst_bloquear_epi_treinamento_vencido',
        'sst_bloquear_epi_treinamento_pendente',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_notification_settings')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach (self::KEYS as $key) {
            $keySql = str_replace("'", "''", $key);
            $exists = $this->fetchRow(
                "SELECT id FROM adms_notification_settings WHERE setting_key = '{$keySql}' LIMIT 1"
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_notification_settings')->insert([
                'setting_key' => $key,
                'enabled' => 0,
                'updated_at' => $now,
            ])->save();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_notification_settings')) {
            return;
        }
        foreach (self::KEYS as $key) {
            $keySql = str_replace("'", "''", $key);
            $this->execute("DELETE FROM adms_notification_settings WHERE setting_key = '{$keySql}' LIMIT 1");
        }
    }
}
