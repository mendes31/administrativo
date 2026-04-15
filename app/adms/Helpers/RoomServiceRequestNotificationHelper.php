<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Notifica membros da equipe responsável (in-app + e-mail) em solicitações de serviço (salas).
 */
final class RoomServiceRequestNotificationHelper
{
    private const KIND_NEW = 'new';

    private const KIND_TEAM_ASSIGNED = 'team_assigned';

    public function __construct(
        private ?RoomServiceRequestsRepository $requestsRepo = null,
        private ?RoomRequestGroupsRepository $groupsRepo = null,
        private ?NotificationsRepository $notificationsRepo = null,
        private ?UsersRepository $usersRepo = null,
    ) {
        $this->requestsRepo = $requestsRepo ?? new RoomServiceRequestsRepository();
        $this->groupsRepo = $groupsRepo ?? new RoomRequestGroupsRepository();
        $this->notificationsRepo = $notificationsRepo ?? new NotificationsRepository();
        $this->usersRepo = $usersRepo ?? new UsersRepository();
    }

    /**
     * Nova solicitação: notifica cada membro do grupo (exceto o solicitante).
     */
    public function notifyGroupOnNewRequest(int $serviceRequestId): void
    {
        $this->notifyGroupMembers($serviceRequestId, self::KIND_NEW);
    }

    /**
     * Equipe responsável alterada (ou definida pela primeira vez na edição): notifica a nova equipe.
     */
    public function notifyGroupOnTeamAssigned(int $serviceRequestId): void
    {
        $this->notifyGroupMembers($serviceRequestId, self::KIND_TEAM_ASSIGNED);
    }

    private function notifyGroupMembers(int $serviceRequestId, string $kind): void
    {
        if ($serviceRequestId <= 0) {
            return;
        }

        $req = $this->requestsRepo->getById($serviceRequestId);
        if ($req === null) {
            return;
        }

        $groupId = !empty($req['responsible_group_id']) ? (int) $req['responsible_group_id'] : 0;
        if ($groupId <= 0) {
            return;
        }

        $memberIds = $this->groupsRepo->getMemberUserIds($groupId);
        if ($memberIds === []) {
            return;
        }

        $requesterId = (int) ($req['requester_user_id'] ?? 0);
        $typeName = (string) ($req['request_type_name'] ?? 'Solicitação');
        $summary = $this->buildSummaryLine($req);
        $link = $this->buildLink($serviceRequestId);

        [$notifType, $title, $subject] = $this->titlesForKind($kind, $typeName);

        $bodyHtml = $this->buildEmailHtml($title, $summary, $link, $kind);
        $altBody = $title . "\n\n" . $summary . "\n\n" . $link;

        $replyToEmail = null;
        $replyToName = null;
        if ($requesterId > 0) {
            $rqUser = $this->usersRepo->getUser($requesterId);
            if (is_array($rqUser)) {
                $re = trim((string) ($rqUser['email'] ?? ''));
                if ($re !== '' && filter_var($re, FILTER_VALIDATE_EMAIL)) {
                    $replyToEmail = $re;
                    $replyToName = (string) ($rqUser['name'] ?? $re);
                }
            }
        }

        foreach ($memberIds as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0 || $uid === $requesterId) {
                continue;
            }

            $this->notificationsRepo->create([
                'user_id' => $uid,
                'type' => $notifType,
                'title' => $title,
                'message' => $summary,
                'link_url' => $link,
                'entity_type' => 'room_service_request',
                'entity_id' => $serviceRequestId,
            ]);

            $userRow = $this->usersRepo->getUser($uid);
            if (!is_array($userRow)) {
                continue;
            }
            $email = trim((string) ($userRow['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = (string) ($userRow['name'] ?? $email);
            try {
                SendEmailService::sendEmail($email, $name, $subject, $bodyHtml, $altBody, $replyToEmail, $replyToName);
            } catch (\Throwable) {
                // SMTP pode falhar; in-app já foi criada
            }
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string} [notification_type, in_app_title, email_subject]
     */
    private function titlesForKind(string $kind, string $typeName): array
    {
        if ($kind === self::KIND_TEAM_ASSIGNED) {
            return [
                'room_service_request_team_assigned',
                'Solicitação (Salas) — atribuída à sua equipe',
                '[Salas] Solicitação atribuída à sua equipe (' . $typeName . ')',
            ];
        }

        return [
            'room_service_request_new',
            'Nova solicitação (Salas): ' . $typeName,
            '[Salas] Nova solicitação: ' . $typeName,
        ];
    }

    private function buildSummaryLine(array $req): string
    {
        $typeName = (string) ($req['request_type_name'] ?? 'Solicitação');
        $date = (string) ($req['service_date'] ?? '');
        $start = substr((string) ($req['start_time'] ?? ''), 0, 5);
        $end = !empty($req['end_time']) ? substr((string) $req['end_time'], 0, 5) : '';
        $location = (string) ($req['location'] ?? '');
        $bookingPart = !empty($req['booking_id']) ? ' (vinculada a reserva)' : '';

        $timePart = $start !== '' ? ($date !== '' ? "{$date} {$start}" : $start) : $date;
        if ($end !== '') {
            $timePart .= '–' . $end;
        }

        return "Tipo: {$typeName}. Quando: {$timePart}. Local: {$location}{$bookingPart}.";
    }

    private function buildLink(int $serviceRequestId): string
    {
        $baseUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');

        return $baseUrl . '/rooms-view-service-request/' . $serviceRequestId;
    }

    private function buildEmailHtml(string $heading, string $summary, string $link, string $kind): string
    {
        $extra = $kind === self::KIND_TEAM_ASSIGNED
            ? '<p>Esta solicitação foi <strong>atribuída à sua equipe</strong> para atendimento. Aceda ao sistema para mais detalhes.</p>'
            : '<p>Foi registada uma <strong>nova solicitação</strong> associada à sua equipe.</p>';

        $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
        $safeSummary = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        return '<p>Olá,</p>' . $extra
            . '<p><strong>' . $safeHeading . '</strong></p>'
            . '<p>' . $safeSummary . '</p>'
            . '<p><a href="' . $safeLink . '">Abrir solicitação no sistema</a></p>';
    }
}
