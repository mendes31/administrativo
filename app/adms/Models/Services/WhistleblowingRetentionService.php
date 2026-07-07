<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingMessagesRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;

/**
 * Retenção LGPD: arquivar após 5 anos, excluir após 10 anos.
 */
final class WhistleblowingRetentionService
{
    public function run(): array
    {
        $reportsRepo = new WhistleblowingReportsRepository();
        $messagesRepo = new WhistleblowingMessagesRepository();

        $archived = $reportsRepo->archiveExpiredReports();
        $deleted = 0;

        foreach ($reportsRepo->getReportIdsDueForDeletion() as $reportId) {
            $attachments = $messagesRepo->getAttachmentsByReportId($reportId);
            foreach ($attachments as $att) {
                $path = WhistleblowingUploadService::getFilePath((string) ($att['stored_name'] ?? ''));
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            if ($reportsRepo->deleteReportById($reportId)) {
                $deleted++;
            }
        }

        return ['archived' => $archived, 'deleted' => $deleted];
    }
}
