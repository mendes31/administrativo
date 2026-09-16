<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

/**
 * Força de vendas do Dashboard de Vendas SAP (scorecard por vendedor).
 */
class CrmSalesVendedores
{
    public function index(): void
    {
        (new CrmSalesAnalyticsPage())->render(
            'vendedores',
            'Força de vendas — Vendas SAP',
            'crm-sales-vendedores',
            'CrmSalesVendedores',
            'adms/Views/crm/sales_vendedores'
        );
    }
}
