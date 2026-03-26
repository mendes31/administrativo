<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Models\Repository\CompanyEventsRepository;

/**
 * JSON para filtro ano/mês no card de eventos do dashboard (AJAX).
 */
class CompanyEventsMonth
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        $y = (int)($_GET['year'] ?? date('Y'));
        $m = (int)($_GET['month'] ?? date('n'));
        if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Período inválido']);
            return;
        }
        try {
            $repo = new CompanyEventsRepository();
            $events = $repo->getEventsIntersectingMonth($y, $m);
            $uid = (int)$_SESSION['user_id'];
            foreach ($events as &$ev) {
                $ev['rsvp'] = $repo->getRsvpForUser((int)$ev['id'], $uid);
            }
            unset($ev);

            echo json_encode(['success' => true, 'events' => $events, 'year' => $y, 'month' => $m]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar eventos']);
        }
    }
}
