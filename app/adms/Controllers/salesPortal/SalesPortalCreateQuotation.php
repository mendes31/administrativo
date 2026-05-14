<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Views\Services\LoadViewService;

/**
 * Formulário para criar cotação no SAP B1 (POST enviado a {@see SalesPortalSaveQuotation}).
 */
class SalesPortalCreateQuotation
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;

        $pageElements = [
            'title_head' => 'Nova cotação — Portal de Vendas',
            'menu' => 'sales-portal-list-quotations',
            'buttonPermission' => [
                'SalesPortalCreateQuotation',
                'SalesPortalSaveQuotation',
                'SalesPortalListQuotations',
            ],
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_sales_portal_save_quotation'),
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/createQuotation', $this->data);
        $loadView->loadView();
    }
}
