<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SacSlaRulesRepository;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacTicketsRepository;

class SacSlaService
{
    /**
     * Calculate SLA deadlines for a new ticket based on its category and priority.
     * Returns array with 'sla_response_deadline' and 'sla_resolution_deadline' as datetime strings,
     * or nulls if no SLA rule found.
     *
     * @param int|null $categoryId
     * @param string $priority
     * @return array{sla_response_deadline: string|null, sla_resolution_deadline: string|null}
     */
    public static function calculateDeadlines(?int $categoryId, string $priority): array
    {
        $result = [
            'sla_response_deadline' => null,
            'sla_resolution_deadline' => null,
        ];

        $slaRepo = new SacSlaRulesRepository();
        $rule = $slaRepo->findRuleForTicket($categoryId ?? 0, $priority);

        if (!$rule) {
            if ($categoryId) {
                $catRepo = new SacCategoriesRepository();
                $cat = $catRepo->getCategoryById($categoryId);
                if ($cat && (!empty($cat['default_sla_response_hours']) || !empty($cat['default_sla_resolution_hours']))) {
                    $now = new \DateTimeImmutable();
                    if (!empty($cat['default_sla_response_hours'])) {
                        $result['sla_response_deadline'] = $now->modify('+' . (int)$cat['default_sla_response_hours'] . ' hours')->format('Y-m-d H:i:s');
                    }
                    if (!empty($cat['default_sla_resolution_hours'])) {
                        $result['sla_resolution_deadline'] = $now->modify('+' . (int)$cat['default_sla_resolution_hours'] . ' hours')->format('Y-m-d H:i:s');
                    }
                }
            }
            return $result;
        }

        $now = new \DateTimeImmutable();
        $responseHours = (int) ($rule['response_time_hours'] ?? 0);
        $resolutionHours = (int) ($rule['resolution_time_hours'] ?? 0);

        if ($responseHours > 0) {
            $result['sla_response_deadline'] = $now->modify('+' . $responseHours . ' hours')->format('Y-m-d H:i:s');
        }
        if ($resolutionHours > 0) {
            $result['sla_resolution_deadline'] = $now->modify('+' . $resolutionHours . ' hours')->format('Y-m-d H:i:s');
        }

        return $result;
    }

    /**
     * Check if a ticket has breached its SLA and update flags if needed.
     */
    public static function checkAndUpdateBreach(int $ticketId): void
    {
        $repo = new SacTicketsRepository();
        $ticket = $repo->getTicketById($ticketId);
        if (!$ticket) {
            return;
        }

        $now = new \DateTimeImmutable();
        $updates = [];

        if (!empty($ticket['sla_response_deadline']) && empty($ticket['first_response_at'])) {
            $deadline = new \DateTimeImmutable($ticket['sla_response_deadline']);
            if ($now > $deadline && empty($ticket['sla_response_breached'])) {
                $updates['sla_response_breached'] = 1;
            }
        }

        if (!empty($ticket['sla_resolution_deadline']) && empty($ticket['resolved_at'])) {
            $deadline = new \DateTimeImmutable($ticket['sla_resolution_deadline']);
            if ($now > $deadline && empty($ticket['sla_resolution_breached'])) {
                $updates['sla_resolution_breached'] = 1;
            }
        }

        if (!empty($updates)) {
            $repo->updateTicket($ticketId, $updates);
        }
    }
}
