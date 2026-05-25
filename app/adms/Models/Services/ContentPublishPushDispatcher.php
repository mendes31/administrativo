<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Dispara apenas Web Push (PWA), sem gravar em adms_notifications.
 * O sino do portal permanece com a lógica original (Comunicados, Políticas, etc.).
 */
final class ContentPublishPushDispatcher
{
    /**
     * @param int[] $userIds
     * @param string $scope
     * @param string $title
     * @param string $body
     * @param string $url
     * @param bool $forceResend
     * @return array{sent:int, skipped:int, failed:int}
     */
    public static function sendToUsers(
        array $userIds,
        string $scope,
        string $title,
        string $body,
        string $url,
        bool $forceResend = false
    ): array {
        $summary = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        $title = trim($title) !== '' ? trim($title) : 'Portal Tiaraju';
        $body = trim($body) !== '' ? trim($body) : $title;
        if (mb_strlen($body) > 200) {
            $body = mb_substr($body, 0, 197) . '...';
        }

        $push = new PushNotificationService();
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId <= 0) {
                continue;
            }

            if (!$forceResend && PublishPushDedupCache::wasSentInScope($scope, $userId, $title, $url)) {
                $summary['skipped']++;
                continue;
            }

            $result = $push->sendToUser($userId, $title, $body, $url);
            if (!empty($result['success'])) {
                $summary['sent']++;
                if (!$forceResend) {
                    PublishPushDedupCache::markSentInScope($scope, $userId, $title, $url);
                }
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    public static function clearDedupForContent(string $type, int $entityId): void
    {
        PublishPushDedupCache::clearScope(PublishPushDedupCache::scopeForEntity($type, $entityId));
    }
}
