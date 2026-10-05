<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Página ACL: Indicadores de Centros de Custo SAP.
 * ACL copiada de FinCashFlowDashboard; fallback CostCenterSummary.
 */
final class RegisterFinCostCenterDashboard extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'FinCashFlowDashboard' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CostCenterSummary' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'FinCostCenterDashboard' LIMIT 1"
        );
        if (!$existing) {
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('Indicadores de Centros de Custo SAP') . ', '
                . $conn->quote('FinCostCenterDashboard') . ', '
                . $conn->quote('fin-cost-center-dashboard') . ', '
                . $conn->quote('financialReports') . ', '
                . $conn->quote('Saldos de centros de custo SAP (Dimensão 1, contas 4. e 5.) no período, com rateio e pendências. Orçamento em versão futura.') . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'FinCashFlowDashboard' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'CostCenterSummary' LIMIT 1"
            );
        }
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'FinCostCenterDashboard' LIMIT 1"
        );
        if ($ref && $page && $this->hasTable('adms_access_levels_pages')) {
            $refId = (int) $ref['id'];
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT alp.permission, alp.adms_access_level_id, {$pageId}, "
                . $conn->quote($now) . ', ' . $conn->quote($now) . "
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refId}
                   AND NOT EXISTS (
                       SELECT 1 FROM adms_access_levels_pages x
                       WHERE x.adms_access_level_id = alp.adms_access_level_id
                         AND x.adms_page_id = {$pageId}
                   )"
            );
        }

        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'FinCostCenterDashboard' LIMIT 1"
        );
        if ($row) {
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }
        $this->bumpMenuCache();
    }

    private function bumpMenuCache(): void
    {
        $cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt',
            (string) time()
        );
    }
}
