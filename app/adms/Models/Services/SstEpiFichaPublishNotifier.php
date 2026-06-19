<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\NotificationsRepository;

/**
 * Notificação in-app (sino) + Web Push ao publicar ficha de EPI para assinatura.
 */
final class SstEpiFichaPublishNotifier
{
    public static function notifyPendingSignature(int $userId, int $fichaId, string $colaboradorNome, bool $forcePush = false): void
    {
        if ($userId <= 0 || $fichaId <= 0) {
            return;
        }

        try {
            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
            $link = $base . 'sign-epi-ficha/' . $fichaId;
            $title = 'Ficha de entrega de EPI';
            $message = 'Confirme o recebimento dos EPIs entregues em ' . date('d/m/Y') . '.';

            $notifRepo = new NotificationsRepository();
            if ($notifRepo->existsForUserEntity($userId, 'sst_epi_ficha', $fichaId, 'sst_epi_ficha_sign')) {
                if ($forcePush) {
                    \App\adms\Helpers\InternalPushNotificationHelper::notifyUser([
                        'user_id' => $userId,
                        'type' => 'sst_epi_ficha_sign',
                        'title' => $title,
                        'message' => $message,
                        'link_url' => $link,
                        'entity_type' => 'sst_epi_ficha',
                        'entity_id' => $fichaId,
                        'force' => true,
                    ]);
                }

                return;
            }

            $notifRepo->create([
                'user_id' => $userId,
                'type' => 'sst_epi_ficha_sign',
                'title' => $title,
                'message' => $message,
                'link_url' => $link,
                'entity_type' => 'sst_epi_ficha',
                'entity_id' => $fichaId,
            ]);
        } catch (\Throwable $e) {
            error_log('SstEpiFichaPublishNotifier::notifyPendingSignature error: ' . $e->getMessage());
        }
    }

    public static function markNotificationsRead(int $userId, int $fichaId): void
    {
        try {
            (new NotificationsRepository())->markAsReadByEntity($userId, 'sst_epi_ficha', $fichaId);
        } catch (\Throwable) {
        }
    }
}
