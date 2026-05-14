<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SapB1MarketingDocumentAttribution;
use App\adms\Models\Services\SapB1ServiceLayer;

/**
 * POST: cria pedido de venda ligado à cotação (linhas BaseType/BaseEntry/BaseLine) e tenta fechar a cotação.
 */
class SalesPortalConvertQuotationToOrder
{
    /** BoObjectTypes.oQuotations (DI) = 23 — usado na Service Layer para linhas desenhadas de cotação. */
    private const QUOTATION_BASE_TYPE = 23;

    public function index(): void
    {
        $listUrl = $_ENV['URL_ADM'] . 'sales-portal-list-quotations';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $listUrl);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_sales_portal_convert_quotation_to_order', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $listUrl);
            exit;
        }

        $connectionId = isset($_POST['connection']) ? (int) $_POST['connection'] : 0;
        $docEntry = isset($_POST['doc_entry']) ? (int) $_POST['doc_entry'] : 0;
        $qConn = $connectionId > 0 ? '?connection=' . $connectionId : '';
        $viewQuotUrl = $_ENV['URL_ADM'] . 'sales-portal-view-quotation?doc_entry=' . $docEntry . $qConn;

        if ($docEntry <= 0) {
            $_SESSION['msg'] = 'Cotação inválida.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $listUrl);
            exit;
        }

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);
        if (!$sl->hasCredentials()) {
            $_SESSION['msg'] = 'Service Layer não disponível: configure uma conexão com URL que contenha b1s.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $resQ = $sl->getQuotationByDocEntry($docEntry, true);
        if (empty($resQ['success']) || !is_array($resQ['data'] ?? null)) {
            $_SESSION['msg'] = $resQ['error'] ?? 'Não foi possível ler a cotação no SAP.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $quotation = $resQ['data'];
        $status = (string) ($quotation['DocumentStatus'] ?? '');
        if ($status !== '' && (stripos($status, 'Close') !== false || stripos($status, 'Cancel') !== false)) {
            $_SESSION['msg'] = 'Esta cotação já está fechada ou cancelada; não é possível converter.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $cardCode = trim((string) ($quotation['CardCode'] ?? ''));
        if ($cardCode === '' || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $cardCode)) {
            $_SESSION['msg'] = 'CardCode da cotação inválido para criar pedido.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $linesRaw = $quotation['DocumentLines'] ?? [];
        $lines = [];
        if (is_array($linesRaw)) {
            if (isset($linesRaw['results']) && is_array($linesRaw['results'])) {
                $lines = $linesRaw['results'];
            } elseif (array_is_list($linesRaw)) {
                $lines = $linesRaw;
            }
        }

        $documentLines = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            if (!array_key_exists('LineNum', $line)) {
                continue;
            }
            $lineNum = (int) $line['LineNum'];
            $documentLines[] = [
                'BaseType' => self::QUOTATION_BASE_TYPE,
                'BaseEntry' => $docEntry,
                'BaseLine' => $lineNum,
            ];
        }

        if ($documentLines === []) {
            $_SESSION['msg'] = 'A cotação não tem linhas para converter em pedido.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $body = [
            'CardCode' => $cardCode,
            'DocumentLines' => $documentLines,
        ];
        $comments = trim((string) ($quotation['Comments'] ?? ''));
        if ($comments !== '') {
            $body['Comments'] = strlen($comments) > 2000 ? substr($comments, 0, 2000) : $comments;
        }

        $body = SapB1MarketingDocumentAttribution::merge($body, null, null);
        $body = SapB1MarketingDocumentAttribution::mergeDefaultsFromEnv($body);

        $resOrder = $sl->postResource('Orders', $body);
        if (empty($resOrder['success'])) {
            $_SESSION['msg'] = $resOrder['error'] ?? 'Não foi possível criar o pedido a partir da cotação.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $viewQuotUrl);
            exit;
        }

        $newDocEntry = 0;
        $data = $resOrder['data'] ?? null;
        if (is_array($data) && isset($data['DocEntry'])) {
            $newDocEntry = (int) $data['DocEntry'];
        }

        $patch = $sl->patchResource('Quotations(' . $docEntry . ')', ['DocumentStatus' => 'bost_Close']);
        if (empty($patch['success'])) {
            $_SESSION['msg'] = 'Pedido criado no SAP. Aviso: não foi possível fechar a cotação automaticamente — '
                . (string) ($patch['error'] ?? 'feche-a no SAP se necessário.');
            $_SESSION['msg_type'] = 'warning';
        } else {
            $_SESSION['msg'] = 'Pedido criado e cotação fechada no SAP B1.';
            $_SESSION['msg_type'] = 'success';
        }

        if ($newDocEntry > 0) {
            $viewOrder = $_ENV['URL_ADM'] . 'sales-portal-view-order?doc_entry=' . $newDocEntry . $qConn;
            header('Location: ' . $viewOrder);
            exit;
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sales-portal-list-orders' . $qConn);
        exit;
    }
}
