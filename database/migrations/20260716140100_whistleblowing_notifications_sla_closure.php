<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * SLA configurável, encerramento formal, notificações e CAPTCHA no acompanhamento.
 */
final class WhistleblowingNotificationsSlaClosure extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            if (!$reports->hasColumn('sla_response_deadline')) {
                $reports->addColumn('sla_response_deadline', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('sla_breach_notified_at')) {
                $reports->addColumn('sla_breach_notified_at', 'datetime', ['null' => true]);
            }
            if (!$reports->hasColumn('closure_outcome')) {
                $reports->addColumn('closure_outcome', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'comment' => 'Procedente, Improcedente, Parcialmente procedente, Arquivado',
                ]);
            }
            if (!$reports->hasColumn('closure_reason_encrypted')) {
                $reports->addColumn('closure_reason_encrypted', 'text', ['null' => true]);
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_categories')) {
            $cats = $this->table('adms_whistleblowing_categories');
            if (!$cats->hasColumn('sla_first_response_hours')) {
                $cats->addColumn('sla_first_response_hours', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'comment' => 'Sobrescreve SLA global; null = usar risco/global',
                ]);
            }
            $cats->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $cfg = $this->table('adms_whistleblowing_config');
            $intCols = [
                'sla_first_response_hours' => 72,
                'sla_hours_critico' => 24,
                'sla_hours_alto' => 48,
                'sla_hours_medio' => 72,
                'sla_hours_baixo' => 120,
                'notify_committee_on_reply' => 1,
                'notify_committee_on_status_change' => 1,
                'notify_committee_on_sla_breach' => 1,
                'notify_reporter_on_reply' => 0,
                'captcha_enabled' => 0,
            ];
            foreach ($intCols as $col => $default) {
                if (!$cfg->hasColumn($col)) {
                    $cfg->addColumn($col, 'integer', [
                        'default' => $default,
                        'signed' => false,
                        'null' => false,
                    ]);
                }
            }
            if (!$cfg->hasColumn('captcha_provider')) {
                $cfg->addColumn('captcha_provider', 'string', ['limit' => 20, 'default' => 'hcaptcha', 'null' => false]);
            }
            if (!$cfg->hasColumn('captcha_site_key')) {
                $cfg->addColumn('captcha_site_key', 'string', ['limit' => 255, 'null' => true]);
            }
            if (!$cfg->hasColumn('captcha_secret_key')) {
                $cfg->addColumn('captcha_secret_key', 'string', ['limit' => 255, 'null' => true]);
            }
            $cfg->update();
        }

        if ($this->hasTable('adms_whistleblowing_reports')) {
            $this->execute(
                "UPDATE adms_whistleblowing_reports
                 SET sla_response_deadline = DATE_ADD(created_at, INTERVAL 72 HOUR)
                 WHERE sla_response_deadline IS NULL
                   AND status != 'Encerrada'
                   AND archived_at IS NULL"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');
            foreach (['sla_response_deadline', 'sla_breach_notified_at', 'closure_outcome', 'closure_reason_encrypted'] as $col) {
                if ($reports->hasColumn($col)) {
                    $reports->removeColumn($col);
                }
            }
            $reports->update();
        }

        if ($this->hasTable('adms_whistleblowing_categories')) {
            $cats = $this->table('adms_whistleblowing_categories');
            if ($cats->hasColumn('sla_first_response_hours')) {
                $cats->removeColumn('sla_first_response_hours');
            }
            $cats->update();
        }

        if ($this->hasTable('adms_whistleblowing_config')) {
            $cfg = $this->table('adms_whistleblowing_config');
            foreach ([
                'sla_first_response_hours', 'sla_hours_critico', 'sla_hours_alto', 'sla_hours_medio', 'sla_hours_baixo',
                'notify_committee_on_reply', 'notify_committee_on_status_change', 'notify_committee_on_sla_breach',
                'notify_reporter_on_reply', 'captcha_enabled', 'captcha_provider', 'captcha_site_key', 'captcha_secret_key',
            ] as $col) {
                if ($cfg->hasColumn($col)) {
                    $cfg->removeColumn($col);
                }
            }
            $cfg->update();
        }
    }
}
