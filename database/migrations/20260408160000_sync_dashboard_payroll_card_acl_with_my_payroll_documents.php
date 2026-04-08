<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * O card "DashboardCardPayrollDocuments" foi criado copiando ACL do card Tempo de Empresa / Dashboard,
 * o que habilitava o card para mais níveis do que o desejável.
 * Alinha permissões com MyPayrollDocuments: só vê o card quem pode aceder a "Meus documentos de folha".
 */
final class SyncDashboardPayrollCardAclWithMyPayrollDocuments extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $card = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardPayrollDocuments' LIMIT 1");
        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'MyPayrollDocuments' LIMIT 1");
        if (!$card || !$ref) {
            return;
        }

        $cardId = (int)$card['id'];
        $refId = (int)$ref['id'];
        $now = date('Y-m-d H:i:s');

        $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$cardId}");

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$cardId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refId}"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $card = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardPayrollDocuments' LIMIT 1");
        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardTempoEmpresa' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'Dashboard' LIMIT 1");
        }
        if (!$card || !$ref) {
            return;
        }

        $cardId = (int)$card['id'];
        $refId = (int)$ref['id'];
        $now = date('Y-m-d H:i:s');

        $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$cardId}");

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$cardId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refId}"
        );
    }
}
