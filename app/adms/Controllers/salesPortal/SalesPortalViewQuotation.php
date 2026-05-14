<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SapB1ServiceLayer;
use App\adms\Views\Services\LoadViewService;

/**
 * Detalhe de uma cotação SAP B1 (`Quotations`) por DocEntry via Service Layer.
 */
class SalesPortalViewQuotation
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;
        $docEntry = isset($_GET['doc_entry']) ? (int) $_GET['doc_entry'] : 0;

        if ($docEntry <= 0) {
            $_SESSION['msg'] = 'Indique um DocEntry válido na URL (doc_entry).';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sales-portal-list-quotations');
            exit;
        }

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);

        $quotation = null;
        $documentLines = [];
        $slError = null;
        $slUnavailable = false;

        if (!$sl->hasCredentials()) {
            $slUnavailable = true;
        } else {
            $res = $sl->getQuotationByDocEntry($docEntry, true);
            if (!empty($res['success'])) {
                $row = $res['data'] ?? null;
                if (is_array($row)) {
                    $quotation = $row;
                    $lines = $row['DocumentLines'] ?? [];
                    if (is_array($lines)) {
                        if (isset($lines['results']) && is_array($lines['results'])) {
                            $documentLines = $lines['results'];
                        } elseif (array_is_list($lines)) {
                            $documentLines = $lines;
                        } else {
                            $documentLines = [];
                        }
                    }
                }
            } else {
                $slError = $res['error'] ?? 'Cotação não encontrada ou erro na Service Layer.';
            }
        }

        $csrfConvert = '';
        $canCopyToSalesOrder = false;
        if ($quotation !== null && is_array($quotation) && !$slUnavailable && $slError === null) {
            $csrfConvert = CSRFHelper::generateCSRFToken('form_sales_portal_convert_quotation_to_order');
            $st = (string) ($quotation['DocumentStatus'] ?? '');
            $lower = strtolower($st);
            $canCopyToSalesOrder = $st === ''
                || str_contains($lower, 'open')
                || str_contains($lower, 'bost_open');
            if (str_contains($lower, 'close') || str_contains($lower, 'cancel')) {
                $canCopyToSalesOrder = false;
            }
        }

        $pageElements = [
            'title_head' => 'Cotação #' . $docEntry . ' — Portal de Vendas',
            'menu' => 'sales-portal-list-quotations',
            'buttonPermission' => ['SalesPortalViewQuotation', 'SalesPortalListQuotations', 'SalesPortalConvertQuotationToOrder'],
            'quotation' => $quotation,
            'document_lines' => $documentLines,
            'doc_entry' => $docEntry,
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'sl_error' => $slError,
            'sl_unavailable' => $slUnavailable,
            'csrf_token_convert_quotation' => $csrfConvert,
            'quotation_can_copy_to_sales_order' => $canCopyToSalesOrder,
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/viewQuotation', $this->data);
        $loadView->loadView();
    }
}
