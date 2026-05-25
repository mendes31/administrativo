<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CompanyEventsRepository;

/**
 * Web Push PWA ao publicar evento corporativo ativo (sino de eventos inalterado).
 */
final class CompanyEventPublishNotifier
{
    /**
     * @param bool $forceAll true = reenvia para todos; false = apenas para quem não recebeu
     * @return array{success:bool, sent:int, skipped:int, failed:int, message:string}
     */
    public static function resendPushNotifications(int $eventId, bool $forceAll = true): array
    {
        if ($eventId <= 0) {
            return self::failResult('Evento inválido.');
        }

        try {
            $repo = new CompanyEventsRepository();
            $event = $repo->getById($eventId);

            if (!$event || empty($event['ativo'])) {
                return self::failResult('Somente eventos ativos podem reenviar push.');
            }

            if (!self::isWithinPublicationWindow($event)) {
                return self::failResult('Evento fora da janela de publicação.');
            }

            if ($forceAll) {
                ContentPublishPushDispatcher::clearDedupForContent('company_event', $eventId);
            }
            $summary = self::dispatchPush($eventId, $event, $forceAll);

            $modeLabel = $forceAll ? 'todos' : 'pendentes';
            return [
                'success' => $summary['sent'] > 0,
                'sent' => $summary['sent'],
                'skipped' => $summary['skipped'],
                'failed' => $summary['failed'],
                'message' => $summary['sent'] > 0
                    ? sprintf(
                        'Push (%s) enviado para %d dispositivo(s). %d ignorado(s) (já recebido). %d sem inscrição push ou com falha.',
                        $modeLabel,
                        $summary['sent'],
                        $summary['skipped'],
                        $summary['failed']
                    )
                    : sprintf(
                        'Nenhum push entregue (%s). %d já havia recebido, %d sem inscrição/falha. Verifique se os colaboradores ativaram notificações no perfil/PWA.',
                        $modeLabel,
                        $summary['skipped'],
                        $summary['failed']
                    ),
            ];
        } catch (\Throwable $e) {
            error_log('CompanyEventPublishNotifier::resendPushNotifications error: ' . $e->getMessage());

            return self::failResult('Erro ao reenviar push: ' . $e->getMessage());
        }
    }

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

            self::dispatchPush($eventId, $event, false);
        } catch (\Throwable $e) {
            error_log('CompanyEventPublishNotifier::notifyPublished error: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $event
     * @return array{sent:int, skipped:int, failed:int}
     */
    private static function dispatchPush(int $eventId, array $event, bool $forceResend): array
    {
        $departmentIds = [];
        $deptId = (int) ($event['department_id'] ?? 0);
        if ($deptId > 0) {
            $departmentIds = [$deptId];
        }

        $authorId = (int) ($event['created_by'] ?? 0);
        $exclude = $authorId > 0 ? [$authorId] : [];

        $userIds = ContentPublishRecipientsResolver::activeUserIds($departmentIds, $exclude);
        if ($userIds === []) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
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

        /** @var string $link */
        /** @var bool $forceResend */
        return ContentPublishPushDispatcher::sendToUsers(
            $userIds,
            $scope,
            'Novo evento corporativo',
            $message,
            $link,
            $forceResend
        );
    }

    /**
     * @return array{success:bool, sent:int, skipped:int, failed:int, message:string}
     */
    private static function failResult(string $message): array
    {
        return [
            'success' => false,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'message' => $message,
        ];
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
