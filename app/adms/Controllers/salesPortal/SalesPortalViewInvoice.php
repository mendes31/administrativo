<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SapB1ServiceLayer;
use App\adms\Views\Services\LoadViewService;

/**
 * Detalhe de fatura de cliente (`Invoices`) por DocEntry via Service Layer.
 */
class SalesPortalViewInvoice
{
    private array|string|null $data = null;

    public function index(): void
    {
        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;
        $docEntry = isset($_GET['doc_entry']) ? (int) $_GET['doc_entry'] : 0;

        if ($docEntry <= 0) {
            $_SESSION['msg'] = 'Indique um DocEntry válido na URL (doc_entry).';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sales-portal-list-invoices');
            exit;
        }

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);

        $invoice = null;
        $documentLines = [];
        $slError = null;
        $slUnavailable = false;

        if (!$sl->hasCredentials()) {
            $slUnavailable = true;
        } else {
            $res = $sl->getInvoiceByDocEntry($docEntry, true);
            if (!empty($res['success'])) {
                $row = $res['data'] ?? null;
                if (is_array($row)) {
                    $invoice = $row;
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
                $slError = $res['error'] ?? 'Fatura não encontrada ou erro na Service Layer.';
            }
        }

        $pageElements = [
            'title_head' => 'Fatura #' . $docEntry . ' — Portal de Vendas',
            'menu' => 'sales-portal-list-invoices',
            'buttonPermission' => ['SalesPortalViewInvoice', 'SalesPortalListInvoices'],
            'invoice' => $invoice,
            'document_lines' => $documentLines,
            'doc_entry' => $docEntry,
            'connection_id' => $connectionId > 0 ? $connectionId : null,
            'sl_error' => $slError,
            'sl_unavailable' => $slUnavailable,
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/salesPortal/viewInvoice', $this->data);
        $loadView->loadView();
    }
}
