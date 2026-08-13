<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fluxo de Caixa SAP (cache MySQL) + lançamentos locais de aplicações.
 */
final class CreateFinCashFlowModule extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_fin_cash_accounts')) {
            $this->table('adms_fin_cash_accounts')
                ->addColumn('sap_gl_account', 'string', ['limit' => 30, 'null' => false])
                ->addColumn('bank_code', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('branch', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('bank_account', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('description', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('account_type', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'BANK',
                    'comment' => 'BANK | CASH | INVESTMENT | TRANSIT | OTHER',
                ])
                ->addColumn('sap_bpl_id', 'integer', ['null' => true])
                ->addColumn('credit_limit', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('include_in_cash_flow', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('include_in_availability', 'boolean', ['null' => false, 'default' => true])
                ->addColumn('type_locked', 'boolean', [
                    'null' => false,
                    'default' => false,
                    'comment' => '1 = tipo definido manualmente; sync não sobrescreve',
                ])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['sap_gl_account'], ['unique' => true, 'name' => 'uk_fin_cash_gl'])
                ->addIndex(['account_type'], ['name' => 'idx_fin_cash_type'])
                ->create();
        }

        if (!$this->hasTable('adms_fin_cash_opening')) {
            $this->table('adms_fin_cash_opening')
                ->addColumn('as_of_date', 'date', ['null' => false])
                ->addColumn('sap_gl_account', 'string', ['limit' => 30, 'null' => false])
                ->addColumn('balance', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addIndex(['as_of_date', 'sap_gl_account'], ['unique' => true, 'name' => 'uk_fin_cash_opening'])
                ->create();
        }

        if (!$this->hasTable('adms_fin_cash_daily')) {
            $this->table('adms_fin_cash_daily')
                ->addColumn('movement_date', 'date', ['null' => false])
                ->addColumn('sap_gl_account', 'string', ['limit' => 30, 'null' => false])
                ->addColumn('sap_bpl_id', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('inflow', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('outflow', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('internal_in', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('internal_out', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addIndex(
                    ['movement_date', 'sap_gl_account', 'sap_bpl_id'],
                    ['unique' => true, 'name' => 'uk_fin_cash_daily']
                )
                ->addIndex(['movement_date'], ['name' => 'idx_fin_cash_daily_date'])
                ->create();
        }

        if (!$this->hasTable('adms_fin_cash_forecasts')) {
            $this->table('adms_fin_cash_forecasts')
                ->addColumn('source_type', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'comment' => 'AR | AP',
                ])
                ->addColumn('due_date', 'date', ['null' => false])
                ->addColumn('sap_bpl_id', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('card_code', 'string', ['limit' => 50, 'null' => false, 'default' => ''])
                ->addColumn('card_name', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
                ->addColumn('doc_entry', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('doc_num', 'integer', ['null' => false, 'default' => 0])
                ->addColumn('installment_id', 'integer', ['null' => false, 'default' => 1])
                ->addColumn('original_amount', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('paid_amount', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('open_amount', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false, 'default' => '0'])
                ->addColumn('synced_at', 'datetime', ['null' => false])
                ->addIndex(['due_date', 'source_type'], ['name' => 'idx_fin_cash_fc_due'])
                ->addIndex(['source_type', 'doc_entry', 'installment_id'], ['name' => 'idx_fin_cash_fc_doc'])
                ->create();
        }

        if (!$this->hasTable('adms_fin_cash_sync_runs')) {
            $this->table('adms_fin_cash_sync_runs')
                ->addColumn('sync_mode', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('date_from', 'date', ['null' => true])
                ->addColumn('date_to', 'date', ['null' => true])
                ->addColumn('rows_fetched', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('rows_upserted', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'running'])
                ->addColumn('error_log', 'text', ['null' => true])
                ->addColumn('message', 'text', ['null' => true])
                ->addColumn('executed_by', 'integer', ['null' => true])
                ->addColumn('started_at', 'datetime', ['null' => false])
                ->addColumn('finished_at', 'datetime', ['null' => true])
                ->addIndex(['status', 'finished_at'])
                ->create();
        }

        if (!$this->hasTable('adms_fin_cash_sync_state')) {
            $this->table('adms_fin_cash_sync_state', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
            ])
                ->addColumn('id', 'integer', ['identity' => false, 'signed' => false, 'null' => false, 'default' => 1])
                ->addColumn('last_mode', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_status', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('last_from_date', 'date', ['null' => true])
                ->addColumn('last_to_date', 'date', ['null' => true])
                ->addColumn('last_success_at', 'datetime', ['null' => true])
                ->addColumn('rows_upserted', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('message', 'text', ['null' => true])
                ->addColumn('updated_at', 'datetime', ['null' => false])
                ->create();

            $now = date('Y-m-d H:i:s');
            $this->execute(
                'INSERT INTO adms_fin_cash_sync_state (id, last_status, message, updated_at) VALUES (1, '
                . "'never', 'Aguardando primeira sincronização.', "
                . $this->getAdapter()->getConnection()->quote($now) . ')'
            );
        }

        if (!$this->hasTable('adms_fin_cash_investments')) {
            $this->table('adms_fin_cash_investments')
                ->addColumn('movement_type', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'comment' => 'APPLICATION | REDEMPTION | YIELD',
                ])
                ->addColumn('category', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'STANDARD',
                    'comment' => 'STANDARD | GUARANTEE',
                ])
                ->addColumn('bank_label', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('account_id', 'integer', ['null' => true])
                ->addColumn('movement_date', 'date', ['null' => false])
                ->addColumn('amount', 'decimal', ['precision' => 18, 'scale' => 2, 'null' => false])
                ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'ACTIVE'])
                ->addColumn('created_by', 'integer', ['null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['movement_date'], ['name' => 'idx_fin_inv_date'])
                ->addIndex(['bank_label', 'category'], ['name' => 'idx_fin_inv_bank'])
                ->addIndex(['status'], ['name' => 'idx_fin_inv_status'])
                ->create();
        }

        $this->registerPages();
    }

    public function down(): void
    {
        $controllers = [
            'DeleteFinCashInvestment',
            'UpdateFinCashInvestment',
            'CreateFinCashInvestment',
            'ListFinCashInvestments',
            'UpdateFinCashAccount',
            'ListFinCashAccounts',
            'FinCashFlowDashboardSync',
            'FinCashFlowDashboardData',
            'FinCashFlowDashboard',
        ];
        foreach ($controllers as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }

        foreach ([
            'adms_fin_cash_investments',
            'adms_fin_cash_sync_state',
            'adms_fin_cash_sync_runs',
            'adms_fin_cash_forecasts',
            'adms_fin_cash_daily',
            'adms_fin_cash_opening',
            'adms_fin_cash_accounts',
        ] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }

        $this->bumpMenuCache();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'CashFlow' LIMIT 1"
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE '%Financeiro%' OR name LIKE '%Relat%' ORDER BY id ASC LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $pages = [
            [
                'name' => 'Dashboard Fluxo de Caixa SAP',
                'controller' => 'FinCashFlowDashboard',
                'controller_url' => 'fin-cash-flow-dashboard',
                'obs' => 'Dashboard de fluxo de caixa diário/mensal com dados SAP B1 (cache local).',
            ],
            [
                'name' => 'API Dashboard Fluxo de Caixa SAP',
                'controller' => 'FinCashFlowDashboardData',
                'controller_url' => 'fin-cash-flow-dashboard-data',
                'obs' => 'Endpoint JSON (KPIs, diário, mensal, drill-down) do fluxo de caixa SAP.',
            ],
            [
                'name' => 'Sync Fluxo de Caixa SAP',
                'controller' => 'FinCashFlowDashboardSync',
                'controller_url' => 'fin-cash-flow-dashboard-sync',
                'obs' => 'Dispara sincronização SAP → cache MySQL do fluxo de caixa.',
            ],
            [
                'name' => 'Contas Financeiras SAP',
                'controller' => 'ListFinCashAccounts',
                'controller_url' => 'list-fin-cash-accounts',
                'obs' => 'Parametrização das contas de caixa/banco/aplicação usadas no fluxo de caixa SAP.',
            ],
            [
                'name' => 'Editar Conta Financeira SAP',
                'controller' => 'UpdateFinCashAccount',
                'controller_url' => 'update-fin-cash-account',
                'obs' => 'Editar tipo, limite e inclusão da conta no fluxo de caixa SAP.',
            ],
            [
                'name' => 'Aplicações Financeiras',
                'controller' => 'ListFinCashInvestments',
                'controller_url' => 'list-fin-cash-investments',
                'obs' => 'Lançamentos locais de aplicação, resgate e rendimento.',
            ],
            [
                'name' => 'Cadastrar Aplicação Financeira',
                'controller' => 'CreateFinCashInvestment',
                'controller_url' => 'create-fin-cash-investment',
                'obs' => 'Formulário para lançar aplicação, resgate ou rendimento.',
            ],
            [
                'name' => 'Editar Aplicação Financeira',
                'controller' => 'UpdateFinCashInvestment',
                'controller_url' => 'update-fin-cash-investment',
                'obs' => 'Editar lançamento local de aplicação financeira.',
            ],
            [
                'name' => 'Apagar Aplicação Financeira',
                'controller' => 'DeleteFinCashInvestment',
                'controller_url' => 'delete-fin-cash-investment',
                'obs' => 'Excluir lançamento local de aplicação financeira.',
            ],
        ];

        foreach ($pages as $p) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($p['controller']) . ' LIMIT 1'
            );
            if ($existing) {
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($p['name']) . ', '
                . $conn->quote($p['controller']) . ', '
                . $conn->quote($p['controller_url']) . ', '
                . $conn->quote('cashFlow') . ', '
                . $conn->quote($p['obs']) . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuCache();
            return;
        }

        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'CashFlow' LIMIT 1");
        if (!$ref) {
            $this->bumpMenuCache();
            return;
        }
        $refId = (int) $ref['id'];

        foreach (array_column($pages, 'controller') as $controller) {
            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if (!$page) {
                continue;
            }
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
