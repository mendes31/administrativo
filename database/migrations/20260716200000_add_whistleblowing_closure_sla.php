<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * SLA opcional para encerramento das denúncias.
 */
final class AddWhistleblowingClosureSla extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            if (!$reports->hasColumn('sla_closure_started_at')) {
                $reports->addColumn('sla_closure_started_at', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('sla_closure_deadline')) {
                $reports->addColumn('sla_closure_deadline', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('sla_closure_breach_notified_at')) {
                $reports->addColumn('sla_closure_breach_notified_at', 'datetime', ['null' => true]);
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $config = $this->table('adms_whistleblowing_config');
            $columns = [
                'sla_closure_enabled' => 0,
                'sla_closure_hours' => 720,
                'sla_closure_hours_critico' => 168,
                'sla_closure_hours_alto' => 360,
                'sla_closure_hours_medio' => 720,
                'sla_closure_hours_baixo' => 1080,
                'notify_committee_on_sla_closure_breach' => 1,
            ];
            foreach ($columns as $column => $default) {
                if (!$config->hasColumn($column)) {
                    $config->addColumn($column, 'integer', [
                        'default' => $default,
                        'signed' => false,
                        'null' => false,
                    ]);
                }
            }
            $config->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            foreach (['sla_closure_started_at', 'sla_closure_deadline', 'sla_closure_breach_notified_at'] as $column) {
                if ($reports->hasColumn($column)) {
                    $reports->removeColumn($column);
                }
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $config = $this->table('adms_whistleblowing_config');
            foreach ([
                'sla_closure_enabled',
                'sla_closure_hours',
                'sla_closure_hours_critico',
                'sla_closure_hours_alto',
                'sla_closure_hours_medio',
                'sla_closure_hours_baixo',
                'notify_committee_on_sla_closure_breach',
            ] as $column) {
                if ($config->hasColumn($column)) {
                    $config->removeColumn($column);
                }
            }
            $config->update();
        }
    }
}
