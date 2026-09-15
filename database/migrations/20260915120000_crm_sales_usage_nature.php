<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Utilização SAP (OUSG) classificada no Portal + quantidade/desconto no cache.
 * Grão do fato passa a incluir usage_id — o cache é esvaziado e exige --full.
 */
final class CrmSalesUsageNature extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('crm_sales_usage_nature')) {
            $this->table('crm_sales_usage_nature', [
                'id' => false,
                'primary_key' => ['usage_id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('usage_id', 'integer', ['signed' => true, 'null' => false])
                ->addColumn('usage_name', 'string', ['limit' => 150, 'null' => false, 'default' => ''])
                ->addColumn('natureza', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'nao_classificada',
                    'comment' => 'venda | bonificacao | brinde | ignorar | nao_classificada',
                ])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['natureza'], ['name' => 'idx_crm_sales_usage_nat'])
                ->create();
        }

        if ($this->hasTable('crm_sales_fact_daily')) {
            $table = $this->table('crm_sales_fact_daily');
            if (!$table->hasColumn('usage_id')) {
                $table
                    ->addColumn('usage_id', 'integer', [
                        'signed' => true,
                        'null' => false,
                        'default' => 0,
                        'after' => 'grupo_item',
                    ])
                    ->addColumn('usage_name', 'string', [
                        'limit' => 150,
                        'null' => false,
                        'default' => '',
                        'after' => 'usage_id',
                    ])
                    ->addColumn('quantidade', 'decimal', [
                        'precision' => 18,
                        'scale' => 4,
                        'null' => false,
                        'default' => '0',
                        'after' => 'valor_liquido',
                    ])
                    ->addColumn('valor_bruto', 'decimal', [
                        'precision' => 18,
                        'scale' => 4,
                        'null' => false,
                        'default' => '0',
                        'after' => 'quantidade',
                    ])
                    ->addColumn('valor_desconto', 'decimal', [
                        'precision' => 18,
                        'scale' => 4,
                        'null' => false,
                        'default' => '0',
                        'after' => 'valor_bruto',
                    ])
                    ->addIndex(['usage_id'], ['name' => 'idx_crm_sales_usage_id'])
                    ->update();
            }

            // Grão antigo não tem utilização: evita duplicar fatos no próximo sync.
            $this->execute('DELETE FROM crm_sales_fact_daily');
        }

        $this->registerPage(
            'CrmListSalesUsages',
            'crm-list-sales-usages',
            'Utilizações de venda SAP (CRM)',
            'Classifica utilizações SAP (OUSG) em venda, bonificação, brinde ou ignorar.'
        );
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'CrmListSalesUsages' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('crm_sales_fact_daily')) {
            $table = $this->table('crm_sales_fact_daily');
            if ($table->hasIndex('idx_crm_sales_usage_id')) {
                $table->removeIndexByName('idx_crm_sales_usage_id');
            }
            foreach (['valor_desconto', 'valor_bruto', 'quantidade', 'usage_name', 'usage_id'] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }

        if ($this->hasTable('crm_sales_usage_nature')) {
            $this->table('crm_sales_usage_nature')->drop()->save();
        }

        $this->bumpMenuCache();
    }

    private function registerPage(string $controller, string $url, string $name, string $obs): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = "
            . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($existing) {
            $this->copyAclFromDashboard((int) $existing['id'], $now);
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
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE '%CRM%' ORDER BY id LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $groupId = (int) $group['id'];

        $this->execute(
            'INSERT INTO adms_pages (
                name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                adms_packages_page_id, adms_groups_page_id, created_at, updated_at
            ) VALUES ('
            . $conn->quote($name) . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote($url) . ', '
            . $conn->quote('crm') . ', '
            . $conn->quote($obs) . ', '
            . '0, 0, 1, 1, ' . $groupId . ', '
            . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
        );

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = " . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($page) {
            $this->copyAclFromDashboard((int) $page['id'], $now);
        }
    }

    private function copyAclFromDashboard(int $pageId, string $now): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $conn = $this->getAdapter()->getConnection();
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'CrmSalesDashboard' LIMIT 1"
        );
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];
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
