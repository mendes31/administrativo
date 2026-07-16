<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * @deprecated Use WhistleblowingNotificationService
 */
final class WhistleblowingCommitteeNotificationService
{
    public function notifyNewReport(
        int $reportId,
        string $protocol,
        string $category,
        string $riskLevel,
        ?int $committeeId
    ): void {
        (new WhistleblowingNotificationService())->notifyNewReport(
            $reportId,
            $protocol,
            $category,
            $riskLevel,
            $committeeId
        );
    }
}
