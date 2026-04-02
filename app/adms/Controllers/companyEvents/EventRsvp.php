<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Helpers\CompanyEventRsvpAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Repository\UsersRepository;

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

        $uid = (int)$_SESSION['user_id'];
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);

        if ($targetUserId > 0) {
            $this->handleAdminRsvp($repo, $event, $eventId, $uid, $targetUserId, $action);
            return;
        }

        if ($action === 'cancel') {
            $ok = $repo->cancelRsvp($eventId, $uid);
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
            $uid,
            $status === 'declined' ? 'declined' : 'confirmed',
            $guests,
            !empty($event['allows_guests']),
            (int)($event['max_guests_per_user'] ?? 0)
        );

        if ($ok) {
            $repo->upsertRead($eventId, $uid);
        }

        $unreadYear = $ok ? $repo->countUnreadIntersectingYear((int)date('Y'), $uid) : null;

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Resposta registrada.' : 'Não foi possível registrar (verifique prazos).',
            'unread_year_count' => $unreadYear,
        ]);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function handleAdminRsvp(
        CompanyEventsRepository $repo,
        array $event,
        int $eventId,
        int $actorUserId,
        int $targetUserId,
        string $action
    ): void {
        $permRepo = new ButtonPermissionUserRepository();
        $raw = $permRepo->buttonPermission([
            'UpdateCompanyEvent',
            'DeleteCompanyEvent',
            'CreateCompanyEvent',
            'CompanyEventReport',
        ]);
        $btnPerms = is_array($raw) ? $raw : [];
        if (!CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $actorUserId, $btnPerms)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para registrar presença de terceiros.']);
            return;
        }

        $usersRepo = new UsersRepository();
        if (!$usersRepo->existsActiveUser($targetUserId)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Colaborador inválido ou inativo.']);
            return;
        }

        if ($action === 'cancel') {
            $ok = $repo->adminCancelRsvpBypassDeadlines($eventId, $targetUserId);
            if ($ok) {
                $repo->upsertRead($eventId, $targetUserId);
            }
            echo json_encode([
                'success' => $ok,
                'message' => $ok ? 'Presença cancelada para o colaborador.' : 'Não foi possível cancelar (sem confirmação ativa).',
            ]);
            return;
        }

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

        $ok = $repo->adminSaveRsvpWithGuestsBypassDeadlines(
            $eventId,
            $targetUserId,
            $status === 'declined' ? 'declined' : 'confirmed',
            $guests,
            !empty($event['allows_guests']),
            (int)($event['max_guests_per_user'] ?? 0)
        );

        if ($ok) {
            $repo->upsertRead($eventId, $targetUserId);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Participação do colaborador atualizada.' : 'Não foi possível registrar (evento sem RSVP obrigatório?).',
        ]);
    }
}
