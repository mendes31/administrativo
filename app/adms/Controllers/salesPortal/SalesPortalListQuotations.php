<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SapB1ServiceLayer;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista cotações no SAP B1 via Service Layer (URL da conexão activa deve conter `b1s`).
 * Filtro opcional por {@see CardCode} (parceiro de negócio).
 */
class SalesPortalListQuotations
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;
        $cardCode = isset($_GET['card_code']) ? trim((string) $_GET['card_code']) : '';
        $top = isset($_GET['top']) ? (int) $_GET['top'] : 50;

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);

        $quotations = [];
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
            $res = $sl->getQuotations($filters, max(1, min(200, $top)));
            if (!empty($res['success'])) {
                $rows = $res['data'] ?? [];
                $quotations = is_array($rows) ? $rows : [];
            } else {
                $slError = $res['error'] ?? 'Não foi possível obter cotações.';
            }
        }

        $pageElements = [
            'title_head' => 'Cotações — Portal de Vendas',
            'menu' => 'sales-portal-list-quotations',
            'buttonPermission' => ['SalesPortalListQuotations', 'SalesPortalViewQuotation', 'SalesPortalCreateQuotation'],
            'quotations' => $quotations,
            'card_code_filter' => $cardCode,
            'top_filter' => max(1, min(200, $top)),
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'sl_error' => $slError,
            'sl_unavailable' => $slUnavailable,
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/listQuotations', $this->data);
        $loadView->loadView();
    }
}
