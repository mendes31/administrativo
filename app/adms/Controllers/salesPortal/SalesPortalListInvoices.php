<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SapB1ServiceLayer;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista faturas de cliente (`Invoices`) no SAP B1 via Service Layer (URL com `b1s`).
 */
class SalesPortalListInvoices
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;
        $cardCode = isset($_GET['card_code']) ? trim((string) $_GET['card_code']) : '';
        $top = isset($_GET['top']) ? (int) $_GET['top'] : 50;

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);

        $invoices = [];
        $slError = null;
        $slUnavailable = false;

        if (!$sl->hasCredentials()) {
            $slUnavailable = true;
        } else {
            $filters = [];
            if ($cardCode !== '') {
                if (!preg_match('/^[A-Za-z0-9_-]{1,20}$/', $cardCode)) {
                    $_SESSION['msg'] = 'CardCode inválido: use até 20 caracteres (letras, dígitos, _ ou -).';
                    $_SESSION['msg_type'] = 'warning';
                    $cardCode = '';
                } else {
                    $filters['card_code'] = $cardCode;
                }
            }
            $res = $sl->getInvoices($filters, max(1, min(200, $top)));
            if (!empty($res['success'])) {
                $rows = $res['data'] ?? [];
                $invoices = is_array($rows) ? $rows : [];
            } else {
                $slError = $res['error'] ?? 'Não foi possível obter faturas.';
            }
        }

        $pageElements = [
            'title_head' => 'Faturas — Portal de Vendas',
            'menu' => 'sales-portal-list-invoices',
            'buttonPermission' => ['SalesPortalListInvoices', 'SalesPortalViewInvoice'],
            'invoices' => $invoices,
            'card_code_filter' => $cardCode,
            'top_filter' => max(1, min(200, $top)),
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'sl_error' => $slError,
            'sl_unavailable' => $slUnavailable,
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/listInvoices', $this->data);
        $loadView->loadView();
    }
}
