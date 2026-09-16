<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

/**
 * Carteira de clientes do Dashboard de Vendas SAP (Pareto, novos, concentração).
 */
class CrmSalesCarteira
{
    public function index(): void
    {
        (new CrmSalesAnalyticsPage())->render(
            'carteira',
            'Carteira de clientes — Vendas SAP',
            'crm-sales-carteira',
            'CrmSalesCarteira',
            'adms/Views/crm/sales_carteira'
        );
    }
}
