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
            $eventIdsToMarkAsRead = [];
            foreach ($events as &$ev) {
                $ev['rsvp'] = $repo->getRsvpForUser((int)$ev['id'], $uid);
                $eid = (int)($ev['id'] ?? 0);
                if ($eid <= 0) {
                    continue;
                }
                $requiresRsvp = !empty($ev['requires_rsvp']);
                if (!$requiresRsvp) {
                    $eventIdsToMarkAsRead[] = $eid;
                    continue;
                }
                $rsvpRow = $ev['rsvp'] ?? null;
                $st = is_array($rsvpRow) ? (string)($rsvpRow['status'] ?? '') : '';
                if (in_array($st, ['confirmed', 'declined', 'cancelled'], true)) {
                    $eventIdsToMarkAsRead[] = $eid;
                }
            }
            unset($ev);
            $repo->markManyAsRead($eventIdsToMarkAsRead, $uid);
            $unreadYear = $repo->countUnreadIntersectingYear($y, $uid);

            echo json_encode([
                'success' => true,
                'events' => $events,
                'year' => $y,
                'month' => $m,
                'unread_year_count' => $unreadYear,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar eventos']);
        }
    }
}
