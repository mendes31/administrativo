<?php

declare(strict_types=1);

namespace App\adms\Controllers\salesPortal;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Services\SapB1ServiceLayer;

/**
 * GET JSON: pesquisa artigos (ItemCode / ItemName) no SAP para formulários do portal.
 */
class SalesPortalSearchItems
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'items' => [], 'error' => 'Sessão inválida.']);

            return;
        }

        $permRepo = new ButtonPermissionUserRepository();
        $allowed = $permRepo->buttonPermission([
            'SalesPortalCreateQuotation',
            'SalesPortalSaveQuotation',
            'SalesPortalListQuotations',
            'SalesPortalCreateOrder',
            'SalesPortalSaveOrder',
            'SalesPortalListOrders',
        ]);
        if (!is_array($allowed) || $allowed === []) {
            http_response_code(403);
            echo json_encode(['success' => false, 'items' => [], 'error' => 'Sem permissão.']);

            return;
        }

        $connectionId = isset($_GET['connection']) ? (int) $_GET['connection'] : 0;
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) > 60) {
            $q = substr($q, 0, 60);
        }
        if ($q !== '' && !preg_match('/^[\p{L}\p{N}\s._\-]+$/u', $q)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'items' => [], 'error' => 'Texto de pesquisa inválido.']);

            return;
        }

        /** @var SapB1ServiceLayer $sl */
        $sl = new SapB1ServiceLayer($connectionId > 0 ? $connectionId : null);
        if (!$sl->hasCredentials()) {
            echo json_encode(['success' => false, 'items' => [], 'error' => 'Service Layer não disponível (URL com b1s).']);

            return;
        }

        $res = $sl->searchSalesItems($q, 40);
        if (empty($res['success'])) {
            echo json_encode([
                'success' => false,
                'items' => [],
                'error' => (string) ($res['error'] ?? 'Erro ao pesquisar artigos.'),
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $rows = $res['data'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = trim((string) ($row['ItemCode'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[] = [
                'ItemCode' => $code,
                'ItemName' => (string) ($row['ItemName'] ?? ''),
            ];
        }

        echo json_encode(['success' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
    }
}
