<?php

declare(strict_types=1);

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomBookingSlotHoldRepository;

/**
 * AJAX: bloqueio temporário de intervalo ao abrir "Reserva rápida" (book-room).
 * URL: room-booking-slot-hold (mapeado em LoadPageAdmAccessLevel).
 */
final class RoomBookingSlotHold
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sessão expirada.']);
            return;
        }

        // Não consumir o token: list / acquire / renew / renew… na mesma página usam o mesmo valor.
        if (!CSRFHelper::validateCSRFToken('form_book_room_slot_hold', $_POST['csrf_token'] ?? '', false)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token de segurança inválido.']);
            return;
        }

        $action = (string) ($_POST['action'] ?? '');
        $repo = new RoomBookingSlotHoldRepository();
        $userId = (int) $_SESSION['user_id'];
        $display = trim((string) ($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Utilizador'));
        if ($display === '') {
            $display = 'Utilizador';
        }

        switch ($action) {
            case 'acquire':
                $roomId = (int) ($_POST['room_id'] ?? 0);
                $start = trim((string) ($_POST['start_datetime'] ?? ''));
                $end = trim((string) ($_POST['end_datetime'] ?? ''));
                if ($roomId <= 0 || $start === '' || $end === '') {
                    echo json_encode(['success' => false, 'error' => 'Dados em falta.']);
                    return;
                }
                $res = $repo->acquire($roomId, $start, $end, $userId, $display);
                if (!empty($res['ok'])) {
                    echo json_encode([
                        'success' => true,
                        'token' => $res['token'],
                        'expires_at' => $res['expires_at'] ?? '',
                    ]);
                    return;
                }
                echo json_encode([
                    'success' => false,
                    'blocked_by' => (string) ($res['blocked_by'] ?? ''),
                    'error' => 'Este horário está a ser reservado por: ' . ($res['blocked_by'] ?? 'outro utilizador'),
                ]);
                return;

            case 'renew':
                $token = trim((string) ($_POST['hold_token'] ?? ''));
                $ok = $repo->renew($token, $userId);
                echo json_encode(['success' => $ok]);
                return;

            case 'release':
                $token = trim((string) ($_POST['hold_token'] ?? ''));
                $repo->releaseByToken($token, $userId);
                echo json_encode(['success' => true]);
                return;

            case 'list':
                $roomId = (int) ($_POST['room_id'] ?? 0);
                $date = trim((string) ($_POST['date'] ?? ''));
                $rows = $repo->listActiveForRoomAndDate($roomId, $date);
                echo json_encode(['success' => true, 'holds' => $rows]);
                return;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
        }
    }
}
