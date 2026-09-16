<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

/**
 * Análise de produto do Dashboard de Vendas SAP (ABC, desconto, mix).
 */
class CrmSalesProdutos
{
    public function index(): void
    {
        (new CrmSalesAnalyticsPage())->render(
            'produtos',
            'Produto — Vendas SAP',
            'crm-sales-produtos',
            'CrmSalesProdutos',
            'adms/Views/crm/sales_produtos'
        );
    }
}
