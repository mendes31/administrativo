<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\NotificationsRepository;

/**
 * Marca notificação interna como lida ao abrir o destino (parâmetro mark_notification na URL).
 */
final class NotificationOpenHelper
{
    public static function markFromRequestIfPresent(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        if (!isset($_GET['mark_notification']) || !is_numeric($_GET['mark_notification'])) {
            return;
        }

        $notificationId = (int) $_GET['mark_notification'];
        if ($notificationId <= 0) {
            return;
        }

        try {
            $repo = new NotificationsRepository();
            if ($repo->markAsRead($notificationId, $userId)) {
                NavbarLayoutCacheHelper::clear();
            }
        } catch (\Throwable $e) {
            error_log('NotificationOpenHelper::markFromRequestIfPresent error: ' . $e->getMessage());
        }
    }
}
