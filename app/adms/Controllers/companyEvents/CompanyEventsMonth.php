<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Models\Repository\CompanyEventsRepository;

/**
 * JSON para o modal de eventos do dashboard (AJAX).
 * month=0 ou ausente: todos os eventos ativos do ano, ordenados por data.
 * month=1–12: restringe ao mês.
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
        $monthRaw = $_GET['month'] ?? '0';
        $allYear = ($monthRaw === '' || $monthRaw === null || (string)$monthRaw === '0');
        if ($y < 2000 || $y > 2100) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ano inválido']);
            return;
        }
        if (!$allYear) {
            $m = (int)$monthRaw;
            if ($m < 1 || $m > 12) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Mês inválido']);
                return;
            }
        }
        try {
            $repo = new CompanyEventsRepository();
            $events = $allYear
                ? $repo->getEventsIntersectingYear($y)
                : $repo->getEventsIntersectingMonth($y, (int)$monthRaw);
            $m = $allYear ? 0 : (int)$monthRaw;
            $uid = (int)$_SESSION['user_id'];
            $eventIdsToMarkAsRead = [];
            foreach ($events as &$ev) {
                $eid = (int)($ev['id'] ?? 0);
                if ($eid > 0) {
                    $repo->autoDeclineRsvpIfDeadlinePassed($eid, $uid);
                }
                $ev['rsvp'] = $repo->getRsvpForUser($eid, $uid);
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
