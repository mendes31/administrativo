<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;

/**
 * Régua D+X: notifica colaboradores com documento publicado e ciência ainda pendente.
 * Os dias (D1, D2, D3) vêm do cadastro do tipo de documento; sem colunas na BD, usa-se régua fixa 1/3/7 dias.
 */
final class PayrollDocumentReminderJob
{
    /** @return int Número de lembretes enviados */
    public function run(): int
    {
        $repo = new EmployeePayrollDocumentsRepository();
        $typesRepo = new PayrollDocumentTypesRepository();
        $ev = new PayrollDocumentEventsRepository();
        $rows = $repo->listPendingSignatureForReminders();
        $sent = 0;
        $now = time();

        $fallbackThresholds = [1, 3, 7];

        foreach ($rows as $doc) {
            $stage = (int)($doc['reminder_stage'] ?? 0);
            if ($stage >= 3 || $stage < 0) {
                continue;
            }

            $code = (string)($doc['document_type'] ?? '');
            $typeRow = $code !== '' ? $typesRepo->findByCode($code) : null;
            $perType = $typeRow !== null && array_key_exists('signature_reminders_enabled', $typeRow);
            if ($perType) {
                if (empty($typeRow['signature_reminders_enabled'])) {
                    continue;
                }
                $thresholds = [
                    max(0, min(365, (int)($typeRow['signature_reminder_day_1'] ?? 1))),
                    max(0, min(365, (int)($typeRow['signature_reminder_day_2'] ?? 3))),
                    max(0, min(365, (int)($typeRow['signature_reminder_day_3'] ?? 7))),
                ];
            } else {
                $thresholds = $fallbackThresholds;
            }

            $needDays = $thresholds[$stage] ?? 999;
            $publishedAt = isset($doc['published_at']) ? (string)$doc['published_at'] : '';
            $pub = $publishedAt !== '' ? strtotime($publishedAt) : false;
            if ($pub === false) {
                continue;
            }
            $days = (int)floor(($now - $pub) / 86400);
            if ($days < $needDays) {
                continue;
            }

            $docId = (int)$doc['id'];
            $userId = (int)$doc['user_id'];
            $title = (string)($doc['title'] ?? 'Documento');
            PayrollDocumentPublishNotifier::notifySignatureReminder($userId, $docId, $title, $stage + 1);
            $repo->updateReminderStage($docId, $stage + 1);
            $ev->insert($docId, $userId, 'reminder_sent', ['stage' => $stage + 1, 'days_since_publish' => $days], null, null);
            $sent++;
        }

        return $sent;
    }
}
