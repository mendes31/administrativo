<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Models\Repository\CompanyEventsRepository;

class EventRsvp
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Use POST']);
            return;
        }

        $eventId = (int)($_POST['event_id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        if ($eventId <= 0 || $action === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $repo = new CompanyEventsRepository();
        $event = $repo->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Evento não encontrado']);
            return;
        }

        if ($action === 'cancel') {
            $ok = $repo->cancelRsvp($eventId, (int)$_SESSION['user_id']);
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Participação cancelada.' : 'Não foi possível cancelar (prazo ou status).',
            ]);
            return;
        }

        // confirmar ou recusar
        $status = $action === 'decline' ? 'declined' : 'confirmed';
        $guestsRaw = $_POST['guests'] ?? null;
        $guests = [];
        if (is_string($guestsRaw)) {
            $decoded = json_decode($guestsRaw, true);
            if (is_array($decoded)) {
                $guests = $decoded;
            }
        } elseif (is_array($guestsRaw)) {
            $guests = $guestsRaw;
        }

        $ok = $repo->saveRsvpWithGuests(
            $eventId,
            (int)$_SESSION['user_id'],
            $status === 'declined' ? 'declined' : 'confirmed',
            $guests,
            !empty($event['allows_guests']),
            (int)($event['max_guests_per_user'] ?? 0)
        );

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Resposta registrada.' : 'Não foi possível registrar (verifique prazos).',
        ]);
    }
}
