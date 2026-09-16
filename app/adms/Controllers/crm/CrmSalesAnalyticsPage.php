<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CrmSalesFilterQuery;
use App\adms\Models\Services\CrmSalesDashboardService;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Shell compartilhado das telas irmãs do Dashboard de Vendas SAP.
 */
final class CrmSalesAnalyticsPage
{
    /**
     * @param 'carteira'|'vendedores'|'produtos' $tela
     */
    public function render(
        string $tela,
        string $title,
        string $menu,
        string $controller,
        string $view
    ): void {
        $filters = CrmSalesFilterQuery::fromGet();

        $pageElements = [
            'title_head' => $title,
            'menu' => $menu,
            'buttonPermission' => [
                $controller,
                'CrmSalesDashboard',
                'CrmSalesCarteira',
                'CrmSalesVendedores',
                'CrmSalesProdutos',
            ],
        ];
        $layout = new PageLayoutService();
        $data = $layout->configurePageElements($pageElements);

        try {
            $service = new CrmSalesDashboardService();
            $payload = match ($tela) {
                'carteira' => $service->getCarteiraData($filters),
                'vendedores' => $service->getVendedoresData($filters),
                default => $service->getProdutosData($filters),
            };
        } catch (Exception $e) {
            $payload = [
                'success' => false,
                'cache_empty' => true,
                'periodo' => ['chave' => '12', 'date_from' => '', 'date_to' => ''],
                'filtros' => [],
                'filtros_opcoes' => ['vendedores' => [], 'grupos_cliente' => [], 'regioes' => []],
                'warning' => $e->getMessage(),
            ];
        }

        $query = $_GET;
        $qs = CrmSalesFilterQuery::build($query);
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        $perms = $data['buttonPermission'] ?? [];

        $data['analytics'] = $payload;
        $data['filters'] = $filters;
        $data['query'] = $query;
        $data['query_string'] = $qs;
        $data['sales_nav_active'] = $tela;
        $data['self_url'] = $base . $menu;
        $data['dashboard_url'] = $base . 'crm-sales-dashboard' . ($qs !== '' ? '?' . $qs : '');
        $data['carteira_url'] = $base . 'crm-sales-carteira';
        $data['vendedores_url'] = $base . 'crm-sales-vendedores';
        $data['produtos_url'] = $base . 'crm-sales-produtos';
        $data['can_dashboard'] = is_array($perms) && in_array('CrmSalesDashboard', $perms, true);
        $data['can_carteira'] = is_array($perms) && in_array('CrmSalesCarteira', $perms, true);
        $data['can_vendedores'] = is_array($perms) && in_array('CrmSalesVendedores', $perms, true);
        $data['can_produtos'] = is_array($perms) && in_array('CrmSalesProdutos', $perms, true);

        $loadView = new LoadViewService($view, $data);
        $loadView->loadView();
    }
}
