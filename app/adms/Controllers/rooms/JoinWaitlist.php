<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;

/**
 * POST: inscreve o usuário logado na lista de espera (slug: join-waitlist → classe JoinWaitlist).
 */
class JoinWaitlist
{
    public function index(): void
    {
        $adm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';

        if (empty($_SESSION['user_id'])) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Faça login para entrar na lista de espera.</div>';
            header('Location: ' . $adm . 'login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = '<div class="alert alert-info" role="alert">Use o calendário da sala para solicitar lista de espera.</div>';
            header('Location: ' . $adm . 'list-meeting-rooms');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_join_waitlist', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido.</div>';
            header('Location: ' . $adm . 'list-meeting-rooms');
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $startRaw = trim((string)($_POST['desired_start_datetime'] ?? ''));
        $durationHours = (float)($_POST['duration_hours'] ?? 1);

        if ($roomId <= 0 || $startRaw === '') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Dados incompletos para lista de espera.</div>';
            header('Location: ' . $adm . 'list-meeting-rooms');
            exit;
        }

        if ($durationHours < 0.5 || $durationHours > 8) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Duração deve ser entre 0,5 e 8 horas.</div>';
            header('Location: ' . $adm . 'book-room?room_id=' . $roomId);
            exit;
        }

        $startTs = strtotime($startRaw);
        if ($startTs === false) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Data/hora de início inválida.</div>';
            header('Location: ' . $adm . 'book-room?room_id=' . $roomId);
            exit;
        }

        $endTs = $startTs + (int)round($durationHours * 3600);
        $startSql = date('Y-m-d H:i:s', $startTs);
        $endSql = date('Y-m-d H:i:s', $endTs);

        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);
        if (!$room || ($room['status'] ?? '') !== 'active') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada ou indisponível.</div>';
            header('Location: ' . $adm . 'list-meeting-rooms');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $waitRepo = new BookingWaitlistRepository();

        if ($waitRepo->hasOverlappingWaiting($roomId, $userId, $startSql, $endSql)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você já está na lista de espera para um horário sobreposto nesta sala.</div>';
            header('Location: ' . $adm . 'book-room?room_id=' . $roomId);
            exit;
        }

        try {
            $priority = $waitRepo->getNextPriority($roomId);
            $waitRepo->create([
                'room_id' => $roomId,
                'user_id' => $userId,
                'desired_start_datetime' => $startSql,
                'desired_end_datetime' => $endSql,
                'priority' => $priority,
                'status' => 'waiting',
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Você entrou na lista de espera. Será notificado se houver vaga.</div>';
        } catch (\Throwable) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível registrar na lista de espera. Tente novamente.</div>';
        }

        header('Location: ' . $adm . 'book-room?room_id=' . $roomId);
        exit;
    }
}
