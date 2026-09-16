<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Inclui DocNum/DocEntry no grão do cache para listar notas (sem parcelas)
 * e contar NFs (faturas − devoluções) no dashboard.
 */
final class CrmSalesFactDocGrain extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('crm_sales_fact_daily')) {
            $table = $this->table('crm_sales_fact_daily');
            if (!$table->hasColumn('doc_num')) {
                $table
                    ->addColumn('doc_num', 'integer', [
                        'signed' => false,
                        'null' => false,
                        'default' => 0,
                        'after' => 'tipo_documento',
                    ])
                    ->addColumn('doc_entry', 'integer', [
                        'signed' => false,
                        'null' => false,
                        'default' => 0,
                        'after' => 'doc_num',
                    ])
                    ->addIndex(['tipo_documento', 'doc_num'], ['name' => 'idx_crm_sales_doc_num'])
                    ->addIndex(['doc_entry'], ['name' => 'idx_crm_sales_doc_entry'])
                    ->update();
            }

            $this->execute('DELETE FROM crm_sales_fact_daily');
        }

        $this->registerInvoicesPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'CrmSalesInvoices' LIMIT 1"
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

        if (!$this->hasTable('crm_sales_fact_daily')) {
            return;
        }
        $table = $this->table('crm_sales_fact_daily');
        foreach (['idx_crm_sales_doc_num', 'idx_crm_sales_doc_entry'] as $idx) {
            if ($table->hasIndex($idx)) {
                $table->removeIndexByName($idx);
            }
        }
        foreach (['doc_entry', 'doc_num'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }

    private function registerInvoicesPage(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesInvoices' LIMIT 1"
        );
        if ($existing) {
            return;
        }

        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Notas fiscais Dashboard Vendas CRM') . ', '
            . $conn->quote('CrmSalesInvoices') . ', '
            . $conn->quote('crm-sales-invoices') . ', '
            . $conn->quote('crm') . ', '
            . $conn->quote('Listagem de notas (venda e devolução) a partir do cache do Dashboard de Vendas SAP.') . ', '
            . '0, 0, 1, 1, '
            . $gid . ', '
            . $conn->quote($now) . ', '
            . $conn->quote($now)
            . ')'
        );

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesInvoices' LIMIT 1"
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
