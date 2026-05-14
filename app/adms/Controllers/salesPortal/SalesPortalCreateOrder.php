<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Views\Services\LoadViewService;

/**
 * Formulário para criar pedido de venda no SAP B1 (POST enviado a {@see SalesPortalSaveOrder}).
 */
class SalesPortalCreateOrder
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;

        $pageElements = [
            'title_head' => 'Novo pedido — Portal de Vendas',
            'menu' => 'sales-portal-list-orders',
            'buttonPermission' => [
                'SalesPortalCreateOrder',
                'SalesPortalSaveOrder',
                'SalesPortalListOrders',
            ],
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_sales_portal_save_order'),
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/createOrder', $this->data);
        $loadView->loadView();
    }
}
