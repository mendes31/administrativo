<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Régua D+X configurável por tipo de documento RH + rota HTTP opcional para cron (token).
 */
final class PayrollDocumentTypeRemindersAndCronPage extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_payroll_document_types')) {
            $t = $this->table('adms_payroll_document_types');
            if (!$t->hasColumn('signature_reminders_enabled')) {
                $t->addColumn('signature_reminders_enabled', 'boolean', [
                    'default' => false,
                    'comment' => 'Lembretes automáticos (cron) para ciência pendente',
                ]);
            }
            if (!$t->hasColumn('signature_reminder_day_1')) {
                $t->addColumn('signature_reminder_day_1', 'integer', [
                    'signed' => false,
                    'null' => false,
                    'default' => 1,
                    'comment' => '1.º lembrete: dias após publicação',
                ]);
            }
            if (!$t->hasColumn('signature_reminder_day_2')) {
                $t->addColumn('signature_reminder_day_2', 'integer', [
                    'signed' => false,
                    'null' => false,
                    'default' => 3,
                    'comment' => '2.º lembrete: dias após publicação',
                ]);
            }
            if (!$t->hasColumn('signature_reminder_day_3')) {
                $t->addColumn('signature_reminder_day_3', 'integer', [
                    'signed' => false,
                    'null' => false,
                    'default' => 7,
                    'comment' => '3.º lembrete: dias após publicação',
                ]);
            }
            $t->update();

            // Tipos que já exigiam ciência passam a ter lembretes ativos (comportamento anterior à régua por tipo).
            try {
                $this->execute(
                    'UPDATE adms_payroll_document_types SET signature_reminders_enabled = 1
                     WHERE requires_signature = 1'
                );
            } catch (\Throwable) {
            }
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'PayrollRemindersCron' LIMIT 1");
        if ($exists) {
            return;
        }

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
        if (!$ref) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $gid = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => 'Cron — lembretes de ciência (folha RH)',
            'controller' => 'PayrollRemindersCron',
            'controller_url' => 'payroll-reminders-cron',
            'directory' => 'portal',
            'obs' => 'Chamada por agendador externo. Token em payroll-cron-config (BD) e ?token= na URL. Texto simples.',
            'public_page' => 1,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'PayrollRemindersCron' LIMIT 1");
            if ($row) {
                $pid = (int)$row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('adms_payroll_document_types')) {
            foreach (
                [
                    'signature_reminder_day_3',
                    'signature_reminder_day_2',
                    'signature_reminder_day_1',
                    'signature_reminders_enabled',
                ] as $col
            ) {
                try {
                    $this->execute('ALTER TABLE adms_payroll_document_types DROP COLUMN ' . $col);
                } catch (\Throwable) {
                }
            }
        }
    }
}
