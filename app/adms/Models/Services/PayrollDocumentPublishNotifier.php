<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\NotificationsRepository;

/**
 * Notificação interna ao publicar documento de folha (portal).
 */
final class PayrollDocumentPublishNotifier
{
    public static function notifyPublished(int $userId, int $docId, string $title, bool $needsSignature): void
    {
        try {
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            $link = $needsSignature
                ? $base . 'sign-payroll-document/' . $docId
                : $base . 'my-payroll-documents';
            $message = $needsSignature
                ? 'Confirme o recebimento do documento: ' . $title
                : 'Novo documento disponível: ' . $title;

            (new NotificationsRepository())->create([
                'user_id' => $userId,
                'type' => 'payroll_document',
                'title' => 'Documento de RH',
                'message' => $message,
                'link_url' => $link,
                'entity_type' => 'employee_payroll_document',
                'entity_id' => $docId,
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * Lembrete D+X para documento ainda sem ciência (notificação interna).
     */
    public static function notifySignatureReminder(int $userId, int $docId, string $title, int $reminderNumber): void
    {
        try {
            $base = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
            $link = $base . 'sign-payroll-document/' . $docId;
            $message = 'Lembrete (' . $reminderNumber . '): confirme o recebimento — ' . $title;

            (new NotificationsRepository())->create([
                'user_id' => $userId,
                'type' => 'payroll_document_reminder',
                'title' => 'Documento de RH — lembrete',
                'message' => $message,
                'link_url' => $link,
                'entity_type' => 'employee_payroll_document',
                'entity_id' => $docId,
            ]);
        } catch (\Throwable) {
        }
    }
}
