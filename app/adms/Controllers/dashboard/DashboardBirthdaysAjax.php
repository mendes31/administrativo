<?php

declare(strict_types=1);

namespace App\adms\Controllers\dashboard;

use App\adms\Helpers\DashboardBirthdayCardsHelper;
use App\adms\Models\Repository\UsersRepository;

/**
 * Listas de aniversário / tempo de empresa por mês (modal do dashboard).
 */
class DashboardBirthdaysAjax
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado'], JSON_UNESCAPED_UNICODE);

            return;
        }

        $type = (string) ($_GET['type'] ?? '');
        $month = (int) ($_GET['month'] ?? 0);
        if ($month < 1 || $month > 12) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mês inválido'], JSON_UNESCAPED_UNICODE);

            return;
        }

        $repo = new UsersRepository();

        try {
            if ($type === 'birthday') {
                $items = $repo->listActiveUsersBirthdaysInMonth($month);
                $html = DashboardBirthdayCardsHelper::renderBirthdayMonthCards($items);
            } elseif ($type === 'tenure') {
                $items = $repo->listActiveUsersCompanyAnniversariesInMonth($month);
                $html = DashboardBirthdayCardsHelper::renderCompanyTenureMonthCards($items);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo inválido'], JSON_UNESCAPED_UNICODE);

                return;
            }

            echo json_encode([
                'success' => true,
                'count' => count($items),
                'html' => $html,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar dados.',
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
