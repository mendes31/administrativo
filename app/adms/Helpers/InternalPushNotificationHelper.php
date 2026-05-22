<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Services\ContentPublishPushDispatcher;
use App\adms\Models\Services\PublishPushDedupCache;

/**
 * Dispara apenas Web Push (PWA), sem alterar o sino / adms_notifications.
 */
final class InternalPushNotificationHelper
{
    /**
     * @param array<string, mixed> $data user_id, type, title, message?, link_url?, entity_type?, entity_id?, force?
     */
    public static function notifyUser(array $data): void
    {
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            $title = trim((string) ($data['title'] ?? 'Portal Tiaraju'));
            $body = trim((string) ($data['message'] ?? ''));
            $url = trim((string) ($data['link_url'] ?? ''));
            if ($url === '') {
                $url = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/notificacoes';
            }

            $entityType = trim((string) ($data['entity_type'] ?? ''));
            $entityId = (int) ($data['entity_id'] ?? 0);
            $scope = $entityType !== '' && $entityId > 0
                ? PublishPushDedupCache::scopeForEntity($entityType, $entityId)
                : 'generic_' . ($data['type'] ?? 'info');

            if (!empty($data['force'])) {
                PublishPushDedupCache::clearScope($scope);
            }

            ContentPublishPushDispatcher::sendToUsers(
                [$userId],
                $scope,
                $title,
                $body !== '' ? $body : $title,
                $url,
                !empty($data['force'])
            );
        } catch (\Throwable $e) {
            error_log('InternalPushNotificationHelper::notifyUser error: ' . $e->getMessage());
        }
    }
}
