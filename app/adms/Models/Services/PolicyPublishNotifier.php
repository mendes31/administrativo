<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PoliciesRepository;

/**
 * Web Push PWA ao publicar política interna ativa.
 * O sino "Políticas Internas" continua baseado apenas em leitura.
 */
final class PolicyPublishNotifier
{
    /**
     * @return array{success:bool, sent:int, skipped:int, failed:int, message:string}
     */
    public static function resendPushNotifications(int $policyId): array
    {
        if ($policyId <= 0) {
            return self::failResult('Política inválida.');
        }

        try {
            $repo = new PoliciesRepository();
            $policy = $repo->getPolicyById($policyId);

            if (!$policy || empty($policy['ativo'])) {
                return self::failResult('Somente políticas ativas podem reenviar push.');
            }

            if (!self::isWithinPublicationWindow($policy)) {
                return self::failResult('Política fora da janela de publicação.');
            }

            ContentPublishPushDispatcher::clearDedupForContent('policy', $policyId);
            $summary = self::dispatchPush($policyId, $policy, true);

            return [
                'success' => $summary['sent'] > 0,
                'sent' => $summary['sent'],
                'skipped' => $summary['skipped'],
                'failed' => $summary['failed'],
                'message' => $summary['sent'] > 0
                    ? sprintf(
                        'Push reenviado para %d dispositivo(s). %d usuário(s) sem inscrição push ou com falha de envio.',
                        $summary['sent'],
                        $summary['failed']
                    )
                    : 'Nenhum push foi entregue. Verifique se os colaboradores ativaram notificações no perfil/PWA.',
            ];
        } catch (\Throwable $e) {
            error_log('PolicyPublishNotifier::resendPushNotifications error: ' . $e->getMessage());

            return self::failResult('Erro ao reenviar push: ' . $e->getMessage());
        }
    }

    public static function notifyPublished(int $policyId): void
    {
        if ($policyId <= 0) {
            return;
        }

        try {
            $repo = new PoliciesRepository();
            $policy = $repo->getPolicyById($policyId);

            if (!$policy || empty($policy['ativo'])) {
                return;
            }

            if (!self::isWithinPublicationWindow($policy)) {
                return;
            }

            self::dispatchPush($policyId, $policy, false);
        } catch (\Throwable $e) {
            error_log('PolicyPublishNotifier::notifyPublished error: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $policy
     * @return array{sent:int, skipped:int, failed:int}
     */
    private static function dispatchPush(int $policyId, array $policy, bool $forceResend): array
    {
        $repo = new PoliciesRepository();
        $departmentIds = $repo->getNotifyDepartmentsIds($policyId);
        $authorId = (int) ($policy['usuario_id'] ?? 0);
        $exclude = $authorId > 0 ? [$authorId] : [];

        $userIds = ContentPublishRecipientsResolver::activeUserIds($departmentIds, $exclude);
        if ($userIds === []) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $titulo = trim((string) ($policy['titulo'] ?? 'Nova política interna'));
        $resumo = trim((string) ($policy['resumo'] ?? ''));
        if ($resumo === '') {
            $plain = strip_tags((string) ($policy['conteudo'] ?? ''));
            $resumo = mb_strlen($plain) > 200 ? mb_substr($plain, 0, 197) . '...' : $plain;
        }

        $urgente = !empty($policy['urgente']);
        $pushTitle = $urgente ? 'Política urgente' : 'Nova política interna';
        $message = $titulo;
        if ($resumo !== '') {
            $message .= ' — ' . $resumo;
        }

        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $base . '/view-policy/' . $policyId;
        $scope = PublishPushDedupCache::scopeForEntity('policy', $policyId);

        return ContentPublishPushDispatcher::sendToUsers(
            $userIds,
            $scope,
            $pushTitle,
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
     * @param array<string, mixed> $policy
     */
    public static function isWithinPublicationWindow(array $policy): bool
    {
        $now = time();

        $publishAt = $policy['publish_at'] ?? null;
        if ($publishAt !== null && $publishAt !== '') {
            $ts = strtotime((string) $publishAt);
            if ($ts !== false && $ts > $now) {
                return false;
            }
        }

        $expireAt = $policy['expire_at'] ?? null;
        if ($expireAt !== null && $expireAt !== '') {
            $ts = strtotime((string) $expireAt);
            if ($ts !== false && $ts <= $now) {
                return false;
            }
        }

        return true;
    }
}
