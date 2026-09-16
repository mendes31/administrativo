<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\CrmSalesDashboardService;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Listagem de notas fiscais (venda e devolução) a partir do cache do Dashboard de Vendas SAP.
 * Aberta por duplo clique em vendedor, cliente ou item. Sem parcelas.
 */
class CrmSalesInvoices
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'periodo' => (string) ($_GET['periodo'] ?? '12'),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'vendedor' => $_GET['vendedor'] ?? null,
            'grupo_cliente' => $_GET['grupo_cliente'] ?? null,
            'regiao' => $_GET['regiao'] ?? null,
            'grupo_item' => $_GET['grupo_item'] ?? null,
            'ano_mes' => $_GET['ano_mes'] ?? null,
            'card_code' => $_GET['card_code'] ?? null,
            'item_code' => $_GET['item_code'] ?? null,
            'origem' => (string) ($_GET['origem'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $pageElements = [
            'title_head' => 'Notas fiscais — Vendas SAP',
            'menu' => 'crm-sales-invoices',
            'buttonPermission' => ['CrmSalesInvoices', 'CrmSalesDashboard'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);

        try {
            $service = new CrmSalesDashboardService();
            $payload = $service->getInvoicesData($filters, $page);
        } catch (Exception $e) {
            $payload = [
                'success' => false,
                'titulo' => 'Notas fiscais',
                'origem' => '',
                'escopo_item' => false,
                'has_doc_num' => false,
                'has_drill' => false,
                'periodo' => ['chave' => '12', 'date_from' => '', 'date_to' => ''],
                'filtros' => [],
                'rows' => [],
                'total_rows' => 0,
                'total_valor' => 0.0,
                'total_quantidade' => 0.0,
                'qtd_venda' => 0,
                'qtd_devolucao' => 0,
                'qtd_nfs' => 0,
                'page' => 1,
                'per_page' => 200,
                'pages' => 1,
                'warning' => $e->getMessage(),
            ];
        }

        $this->data['invoices'] = $payload;
        $query = $_GET;
        unset($query['page'], $query['origem']);
        $this->data['query'] = $query;
        $this->data['query_string'] = $this->buildQueryString($query);
        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['dashboard_url'] = $base . 'crm-sales-dashboard';
        $this->data['self_url'] = $base . 'crm-sales-invoices';

        $loadView = new LoadViewService('adms/Views/crm/sales_invoices', $this->data);
        $loadView->loadView();
    }

    /**
     * @param array<string, mixed> $query
     */
    private function buildQueryString(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $parts[] = rawurlencode((string) $key) . '%5B%5D=' . rawurlencode((string) $item);
                }
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        return implode('&', $parts);
    }
}
