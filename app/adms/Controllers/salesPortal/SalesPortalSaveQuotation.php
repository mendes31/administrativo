<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SapB1MarketingDocumentAttribution;
use App\adms\Models\Services\SapB1ServiceLayer;

/**
 * Grava nova cotação na Service Layer (`POST Quotations`).
 *
 * Inclui campos frequentemente obrigatórios no B1 (filial, depósito, vendedor, condição de pagamento,
 * utilização / código de imposto por linha, projeto) quando preenchidos.
 */
class SalesPortalSaveQuotation
{
    public function index(): void
    {
        $redirectCreate = $_ENV['URL_ADM'] . 'sales-portal-create-quotation';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirectCreate);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_sales_portal_save_quotation', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectCreate);
            exit;
        }

        $connectionId = isset($_POST['connection']) ? (int) $_POST['connection'] : 0;
        $qConn = $connectionId > 0 ? '?connection=' . $connectionId : '';
        $redirectCreateConn = $redirectCreate . $qConn;

        $cardCode = trim((string) ($_POST['card_code'] ?? ''));
        if ($cardCode === '' || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $cardCode)) {
            $_SESSION['msg'] = 'Indique um CardCode válido (até 20 caracteres: letras, dígitos, _ ou -).';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $docDate = trim((string) ($_POST['doc_date'] ?? ''));
        if ($docDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $docDate)) {
            $_SESSION['msg'] = 'Data do documento inválida (use AAAA-MM-DD).';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $docDueDate = trim((string) ($_POST['doc_due_date'] ?? ''));
        if ($docDueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $docDueDate)) {
            $_SESSION['msg'] = 'Data «Válido até» inválida (use AAAA-MM-DD).';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $comments = trim((string) ($_POST['comments'] ?? ''));
        if (strlen($comments) > 2000) {
            $comments = substr($comments, 0, 2000);
        }

        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        if (strlen($contactPerson) > 100) {
            $contactPerson = substr($contactPerson, 0, 100);
        }

        $numAtCard = trim((string) ($_POST['num_at_card'] ?? ''));
        if (strlen($numAtCard) > 100) {
            $numAtCard = substr($numAtCard, 0, 100);
        }

        $docCurrency = strtoupper(trim((string) ($_POST['doc_currency'] ?? '')));
        if ($docCurrency !== '' && !preg_match('/^[A-Z]{3}$/', $docCurrency)) {
            $_SESSION['msg'] = 'Moeda inválida: use três letras (ex.: BRL) ou deixe em branco.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $bplId = isset($_POST['bpl_id']) ? (int) $_POST['bpl_id'] : 0;
        $series = isset($_POST['series']) ? (int) $_POST['series'] : 0;

        $paymentGroupRaw = trim((string) ($_POST['payment_group_code'] ?? ''));
        if ($paymentGroupRaw !== '' && !preg_match('/^-?[A-Za-z0-9]{1,15}$/', $paymentGroupRaw)) {
            $_SESSION['msg'] = 'Condição de pagamento (PaymentGroupCode) inválida.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $projectCode = trim((string) ($_POST['project_code'] ?? ''));
        if (strlen($projectCode) > 20) {
            $projectCode = substr($projectCode, 0, 20);
        }
        if ($projectCode !== '' && !preg_match('/^[A-Za-z0-9._\-]{1,20}$/', $projectCode)) {
            $_SESSION['msg'] = 'Código de projeto inválido.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $documentsOwner = isset($_POST['documents_owner']) ? (int) $_POST['documents_owner'] : 0;
        $salesPersonCode = isset($_POST['sales_person_code']) ? (int) $_POST['sales_person_code'] : 0;

        $itemCodes = $_POST['line_item_code'] ?? [];
        $quantities = $_POST['line_quantity'] ?? [];
        $unitPrices = $_POST['line_unit_price'] ?? [];
        $lineWarehouses = $_POST['line_warehouse_code'] ?? [];
        $lineUsages = $_POST['line_usage'] ?? [];
        $lineTaxCodes = $_POST['line_tax_code'] ?? [];

        if (!is_array($itemCodes) || !is_array($quantities) || !is_array($unitPrices)) {
            $_SESSION['msg'] = 'Dados de linhas inválidos.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $documentLines = [];
        $max = min(200, max(
            count($itemCodes),
            count($quantities),
            count($unitPrices),
            is_array($lineWarehouses) ? count($lineWarehouses) : 0,
            is_array($lineUsages) ? count($lineUsages) : 0,
            is_array($lineTaxCodes) ? count($lineTaxCodes) : 0
        ));
        for ($i = 0; $i < $max; $i++) {
            $ic = isset($itemCodes[$i]) ? trim((string) $itemCodes[$i]) : '';
            if ($ic === '') {
                continue;
            }
            if (strlen($ic) > 50) {
                $_SESSION['msg'] = 'ItemCode demasiado longo numa das linhas.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $redirectCreateConn);
                exit;
            }
            $qty = isset($quantities[$i]) ? (float) str_replace(',', '.', (string) $quantities[$i]) : 0.0;
            $priceRaw = isset($unitPrices[$i]) ? trim((string) $unitPrices[$i]) : '';
            $price = $priceRaw === '' ? null : (float) str_replace(',', '.', $priceRaw);
            if ($qty <= 0) {
                $_SESSION['msg'] = 'Cada linha com artigo deve ter quantidade maior que zero.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $redirectCreateConn);
                exit;
            }

            $wh = isset($lineWarehouses[$i]) ? trim((string) $lineWarehouses[$i]) : '';
            if ($wh !== '' && !preg_match('/^[A-Za-z0-9_-]{1,15}$/', $wh)) {
                $_SESSION['msg'] = 'Código de depósito inválido numa das linhas.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $redirectCreateConn);
                exit;
            }

            $usageRaw = isset($lineUsages[$i]) ? trim((string) $lineUsages[$i]) : '';
            $usageInt = null;
            if ($usageRaw !== '') {
                if (!preg_match('/^-?[0-9]{1,4}$/', $usageRaw)) {
                    $_SESSION['msg'] = 'Utilização (Usage) deve ser um número inteiro numa das linhas.';
                    $_SESSION['msg_type'] = 'warning';
                    header('Location: ' . $redirectCreateConn);
                    exit;
                }
                $usageInt = (int) $usageRaw;
            }

            $tax = isset($lineTaxCodes[$i]) ? trim((string) $lineTaxCodes[$i]) : '';
            if (strlen($tax) > 20) {
                $_SESSION['msg'] = 'Código de imposto demasiado longo numa das linhas.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $redirectCreateConn);
                exit;
            }
            if ($tax !== '' && !preg_match('/^[A-Za-z0-9._\-]{1,20}$/', $tax)) {
                $_SESSION['msg'] = 'Código de imposto inválido numa das linhas.';
                $_SESSION['msg_type'] = 'warning';
                header('Location: ' . $redirectCreateConn);
                exit;
            }

            $line = [
                'ItemCode' => $ic,
                'Quantity' => $qty,
            ];
            if ($price !== null && $price > 0) {
                $line['UnitPrice'] = $price;
            }
            if ($wh !== '') {
                $line['WarehouseCode'] = $wh;
            }
            if ($usageInt !== null) {
                $line['Usage'] = $usageInt;
            }
            if ($tax !== '') {
                $line['TaxCode'] = $tax;
            }
            $documentLines[] = $line;
        }

        if ($documentLines === []) {
            $_SESSION['msg'] = 'Adicione pelo menos uma linha com código de artigo.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);
        if (!$sl->hasCredentials()) {
            $_SESSION['msg'] = 'Service Layer não disponível: configure uma conexão com URL que contenha b1s.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirectCreateConn);
            exit;
        }

        $body = [
            'CardCode' => $cardCode,
            'DocumentLines' => $documentLines,
        ];

        if ($docDate !== '') {
            $body['DocDate'] = $docDate;
        }
        if ($docDueDate !== '') {
            $body['DocDueDate'] = $docDueDate;
        }
        if ($comments !== '') {
            $body['Comments'] = $comments;
        }
        if ($contactPerson !== '') {
            $body['ContactPerson'] = $contactPerson;
        }
        if ($numAtCard !== '') {
            $body['NumAtCard'] = $numAtCard;
        }
        if ($docCurrency !== '') {
            $body['DocCurrency'] = $docCurrency;
        }
        if ($bplId > 0) {
            $body['BPL_IDAssignedToInvoice'] = $bplId;
        }
        if ($series > 0) {
            $body['Series'] = $series;
        }
        if ($paymentGroupRaw !== '') {
            if (preg_match('/^-?\d+$/', $paymentGroupRaw)) {
                $body['PaymentGroupCode'] = (int) $paymentGroupRaw;
            } else {
                $body['PaymentGroupCode'] = $paymentGroupRaw;
            }
        }
        if ($projectCode !== '') {
            $body['Project'] = $projectCode;
        }

        $body = SapB1MarketingDocumentAttribution::merge(
            $body,
            $documentsOwner > 0 ? $documentsOwner : null,
            $salesPersonCode > 0 ? $salesPersonCode : null
        );
        $body = SapB1MarketingDocumentAttribution::mergeDefaultsFromEnv($body);

        $res = $sl->postResource('Quotations', $body);
        if (!empty($res['success'])) {
            $data = $res['data'] ?? null;
            $docEntry = 0;
            if (is_array($data) && isset($data['DocEntry'])) {
                $docEntry = (int) $data['DocEntry'];
            }
            $_SESSION['msg'] = 'Cotação criada com sucesso no SAP B1.';
            $_SESSION['msg_type'] = 'success';
            if ($docEntry > 0) {
                $view = $_ENV['URL_ADM'] . 'sales-portal-view-quotation?doc_entry=' . $docEntry;
                if ($connectionId > 0) {
                    $view .= '&connection=' . $connectionId;
                }
                header('Location: ' . $view);
                exit;
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'sales-portal-list-quotations' . $qConn);
            exit;
        }

        $_SESSION['msg'] = $res['error'] ?? 'Não foi possível criar a cotação na Service Layer.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $redirectCreateConn);
        exit;
    }
}
