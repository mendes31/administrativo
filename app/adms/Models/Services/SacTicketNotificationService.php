<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\NotificationsRepository;

class SacTicketNotificationService
{
    /**
     * Notify the assigned agent when a new ticket is created.
     */
    public static function notifyNewTicket(int $ticketId, string $ticketCode, string $subject, ?int $assignedUserId): void
    {
        if (!$assignedUserId || $assignedUserId <= 0) {
            return;
        }

        $creatorId = (int) ($_SESSION['user_id'] ?? 0);
        if ($assignedUserId === $creatorId) {
            return;
        }

        $message = "Novo chamado SAC #{$ticketCode} atribuído a você: " . mb_substr($subject, 0, 80);
        $link = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticketId;

        $repo = new NotificationsRepository();
        $repo->create([
            'user_id' => $assignedUserId,
            'title' => 'Novo chamado SAC',
            'message' => $message,
            'link_url' => $link,
            'type' => 'sac',
        ]);
    }

    /**
     * Notify the assigned agent when a reply is added to their ticket.
     */
    public static function notifyNewReply(int $ticketId, string $ticketCode, ?int $assignedUserId, bool $isInternalNote = false): void
    {
        if (!$assignedUserId || $assignedUserId <= 0) {
            return;
        }

        $senderId = (int) ($_SESSION['user_id'] ?? 0);
        if ($assignedUserId === $senderId) {
            return;
        }

        $noteLabel = $isInternalNote ? 'nota interna' : 'resposta';
        $message = "Nova {$noteLabel} no chamado SAC #{$ticketCode}.";
        $link = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticketId;

        $repo = new NotificationsRepository();
        $repo->create([
            'user_id' => $assignedUserId,
            'title' => 'Atualização em chamado SAC',
            'message' => $message,
            'link_url' => $link,
            'type' => 'sac',
        ]);
    }

    /**
     * Notify when a ticket is transferred to a new agent.
     */
    public static function notifyTransfer(int $ticketId, string $ticketCode, ?int $newAssignedUserId): void
    {
        if (!$newAssignedUserId || $newAssignedUserId <= 0) {
            return;
        }

        $senderId = (int) ($_SESSION['user_id'] ?? 0);
        if ($newAssignedUserId === $senderId) {
            return;
        }

        $message = "Chamado SAC #{$ticketCode} foi transferido para você.";
        $link = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticketId;

        $repo = new NotificationsRepository();
        $repo->create([
            'user_id' => $newAssignedUserId,
            'title' => 'Chamado SAC transferido',
            'message' => $message,
            'link_url' => $link,
            'type' => 'sac',
        ]);
    }

    /**
     * Notify about SLA breach.
     */
    public static function notifySlaBreached(int $ticketId, string $ticketCode, ?int $assignedUserId, string $breachType = 'response'): void
    {
        if (!$assignedUserId || $assignedUserId <= 0) {
            return;
        }

        $typeLabel = $breachType === 'response' ? 'resposta' : 'resolução';
        $message = "SLA de {$typeLabel} do chamado #{$ticketCode} foi violado.";
        $link = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticketId;

        $repo = new NotificationsRepository();
        $repo->create([
            'user_id' => $assignedUserId,
            'title' => 'SLA violado - SAC',
            'message' => $message,
            'link_url' => $link,
            'type' => 'sac',
        ]);
    }
}
