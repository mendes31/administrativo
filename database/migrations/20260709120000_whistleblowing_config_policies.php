<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Políticas LGPD editáveis, agendamento do cron e limites de tentativas no acompanhamento.
 */
final class WhistleblowingConfigPolicies extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_config')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_config');

        if (!$table->hasColumn('retention_archive_years')) {
            $table->addColumn('retention_archive_years', 'integer', [
                'default' => 5,
                'signed' => false,
                'null' => false,
                'comment' => 'Anos até arquivar denúncia',
            ]);
        }
        if (!$table->hasColumn('retention_delete_years')) {
            $table->addColumn('retention_delete_years', 'integer', [
                'default' => 10,
                'signed' => false,
                'null' => false,
                'comment' => 'Anos até exclusão definitiva',
            ]);
        }
        if (!$table->hasColumn('cron_enabled')) {
            $table->addColumn('cron_enabled', 'boolean', ['default' => true, 'null' => false]);
        }
        if (!$table->hasColumn('cron_time')) {
            $table->addColumn('cron_time', 'string', ['limit' => 5, 'default' => '02:00', 'null' => false]);
        }
        if (!$table->hasColumn('rate_limit_max_attempts')) {
            $table->addColumn('rate_limit_max_attempts', 'integer', [
                'default' => 5,
                'signed' => false,
                'null' => false,
            ]);
        }
        if (!$table->hasColumn('rate_limit_window_minutes')) {
            $table->addColumn('rate_limit_window_minutes', 'integer', [
                'default' => 15,
                'signed' => false,
                'null' => false,
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_whistleblowing_config')) {
            return;
        }

        $table = $this->table('adms_whistleblowing_config');
        foreach ([
            'retention_archive_years',
            'retention_delete_years',
            'cron_enabled',
            'cron_time',
            'rate_limit_max_attempts',
            'rate_limit_window_minutes',
        ] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
