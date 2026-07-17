<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Prazo configurável para retorno do denunciante após mensagem pública do comitê.
 */
final class AddWhistleblowingReporterInactivity extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            if (!$reports->hasColumn('reporter_response_requested_at')) {
                $reports->addColumn('reporter_response_requested_at', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('reporter_response_deadline')) {
                $reports->addColumn('reporter_response_deadline', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('reporter_inactivity_notified_at')) {
                $reports->addColumn('reporter_inactivity_notified_at', 'datetime', ['null' => true]);
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $config = $this->table('adms_whistleblowing_config');
            if (!$config->hasColumn('reporter_inactivity_enabled')) {
                $config->addColumn('reporter_inactivity_enabled', 'integer', [
                    'default' => 0,
                    'signed' => false,
                    'null' => false,
                ]);
            }
            if (!$config->hasColumn('reporter_inactivity_days')) {
                $config->addColumn('reporter_inactivity_days', 'integer', [
                    'default' => 15,
                    'signed' => false,
                    'null' => false,
                ]);
            }
            $config->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            foreach ([
                'reporter_response_requested_at',
                'reporter_response_deadline',
                'reporter_inactivity_notified_at',
            ] as $column) {
                if ($reports->hasColumn($column)) {
                    $reports->removeColumn($column);
                }
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $config = $this->table('adms_whistleblowing_config');
            foreach (['reporter_inactivity_enabled', 'reporter_inactivity_days'] as $column) {
                if ($config->hasColumn($column)) {
                    $config->removeColumn($column);
                }
            }
            $config->update();
        }
    }
}
