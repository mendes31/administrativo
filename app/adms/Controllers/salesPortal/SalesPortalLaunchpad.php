<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class SalesPortalLaunchpad
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Painel — Portal de Vendas (SAP)',
            'menu' => 'sales-portal-launchpad',
            'buttonPermission' => [
                'SalesPortalLaunchpad',
                'SalesPortalListQuotations',
                'SalesPortalListOrders',
                'SalesPortalListInvoices',
                'SalesPortalCreateQuotation',
                'SalesPortalCreateOrder',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/launchpad', $this->data);
        $loadView->loadView();
    }
}
