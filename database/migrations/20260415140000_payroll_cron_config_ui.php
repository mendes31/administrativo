<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Token HTTP do cron de lembretes folha — configurável na aplicação (sem .env).
 */
final class PayrollCronConfigUi extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_payroll_cron_config')) {
            $this->table('adms_payroll_cron_config', ['id' => 'id', 'primary_key' => ['id']])
                ->addColumn('http_cron_token', 'string', [
                    'limit' => 512,
                    'null' => true,
                    'comment' => 'Token secreto para ?token= na rota payroll-reminders-cron',
                ])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'PayrollCronConfig' LIMIT 1");
        if ($exists) {
            return;
        }

        $refAcl = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
        if (!$refAcl) {
            return;
        }

        $gestao = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas' LIMIT 1");
        $gid = (int)($gestao['id'] ?? 0);
        if ($gid <= 0) {
            $refImp = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
            $gid = (int)($refImp['adms_groups_page_id'] ?? 0);
        }
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Configuração — cron lembretes folha (RH)',
            'controller' => 'PayrollCronConfig',
            'controller_url' => 'payroll-cron-config',
            'directory' => 'portal',
            'obs' => 'Define o token HTTP para o agendador chamar payroll-reminders-cron. Régua D+X por tipo de documento.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int)($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $refId = (int)$refAcl['id'];
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refId}"
        );
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'PayrollCronConfig' LIMIT 1");
            if ($row) {
                $pid = (int)$row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }
        if ($this->hasTable('adms_payroll_cron_config')) {
            $this->table('adms_payroll_cron_config')->drop()->save();
        }
    }
}
