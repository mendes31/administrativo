<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingReportsRepository;

/**
 * Verifica denúncias com SLA de primeira resposta estourado e dispara alertas.
 */
final class WhistleblowingSlaBreachService
{
    public function processPendingBreaches(): int
    {
        $repo = new WhistleblowingReportsRepository();
        $reports = $repo->findReportsWithSlaBreachPendingNotification();
        $notifier = new WhistleblowingNotificationService();
        $count = 0;

        foreach ($reports as $report) {
            $reportId = (int) ($report['id'] ?? 0);
            if ($reportId <= 0) {
                continue;
            }
            $notifier->notifySlaBreach(
                $reportId,
                (string) ($report['protocol'] ?? ''),
                isset($report['committee_id']) ? (int) $report['committee_id'] : null
            );
            if ($repo->markSlaBreachNotified($reportId)) {
                $count++;
            }
        }

        return $count;
    }
}
