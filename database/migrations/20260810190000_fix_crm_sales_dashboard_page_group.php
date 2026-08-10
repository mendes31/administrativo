<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige grupo ACL das páginas do Dashboard de Vendas CRM
 * (pai "CRM" -> mesmo grupo de CrmDashboard, tipicamente "CRM - Operação").
 */
final class FixCrmSalesDashboardPageGroup extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'CRM - Operação' LIMIT 1"
            );
        }
        if (!$ref) {
            return;
        }

        $gid = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $this->execute(
            'UPDATE adms_pages SET adms_groups_page_id = ' . $gid
            . ', updated_at = ' . $conn->quote($now)
            . " WHERE controller IN ('CrmSalesDashboard', 'CrmSalesDashboardData', 'CrmSalesDashboardSync')"
            . ' AND adms_groups_page_id <> ' . $gid
        );

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

    public function down(): void
    {
        // Intencional: não reverte para o grupo pai legado.
    }
}
