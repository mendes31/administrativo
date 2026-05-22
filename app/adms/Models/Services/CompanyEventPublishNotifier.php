<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CompanyEventsRepository;

/**
 * Web Push PWA ao publicar evento corporativo ativo (sino de eventos inalterado).
 */
final class CompanyEventPublishNotifier
{
    public static function notifyPublished(int $eventId): void
    {
        if ($eventId <= 0) {
            return;
        }

        try {
            $repo = new CompanyEventsRepository();
            $event = $repo->getById($eventId);

            if (!$event || empty($event['ativo'])) {
                return;
            }

            if (!self::isWithinPublicationWindow($event)) {
                return;
            }

            $departmentIds = [];
            $deptId = (int) ($event['department_id'] ?? 0);
            if ($deptId > 0) {
                $departmentIds = [$deptId];
            }

            $authorId = (int) ($event['created_by'] ?? 0);
            $exclude = $authorId > 0 ? [$authorId] : [];

            $userIds = ContentPublishRecipientsResolver::activeUserIds($departmentIds, $exclude);
            if ($userIds === []) {
                return;
            }

            $title = trim((string) ($event['title'] ?? 'Novo evento'));
            $location = trim((string) ($event['location'] ?? ''));
            $message = $title;
            if ($location !== '') {
                $message .= ' — ' . $location;
            }

            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $link = $base . '/view-company-event/' . $eventId;
            $scope = PublishPushDedupCache::scopeForEntity('company_event', $eventId);

            ContentPublishPushDispatcher::sendToUsers(
                $userIds,
                $scope,
                'Novo evento corporativo',
                $message,
                $link,
                false
            );
        } catch (\Throwable $e) {
            error_log('CompanyEventPublishNotifier::notifyPublished error: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    public static function isWithinPublicationWindow(array $event): bool
    {
        $now = time();

        $publishAt = $event['publish_at'] ?? null;
        if ($publishAt !== null && $publishAt !== '') {
            $ts = strtotime((string) $publishAt);
            if ($ts !== false && $ts > $now) {
                return false;
            }
        }

        $expireAt = $event['expire_at'] ?? null;
        if ($expireAt !== null && $expireAt !== '') {
            $ts = strtotime((string) $expireAt);
            if ($ts !== false && $ts <= $now) {
                return false;
            }
        }

        return true;
    }
}
