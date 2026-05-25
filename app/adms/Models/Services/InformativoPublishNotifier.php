<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\InformativosRepository;

/**
 * Web Push PWA ao publicar informativo ativo.
 * O sino "Comunicados" continua baseado apenas em leitura (sem adms_notifications).
 */
final class InformativoPublishNotifier
{
    /**
     * @param bool $forceAll true = reenvia para todos; false = apenas para quem não recebeu
     * @return array{success:bool, sent:int, skipped:int, failed:int, message:string}
     */
    public static function resendPushNotifications(int $informativoId, bool $forceAll = true, int $senderUserId = 0): array
    {
        if ($informativoId <= 0) {
            return self::failResult('Informativo inválido.');
        }

        try {
            $repo = new InformativosRepository();
            $informativo = $repo->getInformativoById($informativoId);

            if (!$informativo || empty($informativo['ativo'])) {
                return self::failResult('Somente informativos ativos podem reenviar push.');
            }

            if (!self::isWithinPublicationWindow($informativo)) {
                return self::failResult('Informativo fora da janela de publicação (aguardando publicação ou já expirado).');
            }

            $scope = PublishPushDedupCache::scopeForEntity('informativo', $informativoId);

            if (!$forceAll && !PublishPushDedupCache::scopeHasRecords($scope)) {
                return self::failResult(
                    'Não há registros de envio anterior para este informativo. '
                    . 'Use "Todos os colaboradores" para o primeiro reenvio; '
                    . 'a partir daí, "Somente quem não recebeu" funcionará corretamente.'
                );
            }

            if ($forceAll) {
                ContentPublishPushDispatcher::clearDedupForContent('informativo', $informativoId);
            }
            $summary = self::dispatchPush($informativoId, $informativo, $forceAll, $senderUserId);

            $modeLabel = $forceAll ? 'todos' : 'pendentes';
            return self::buildResultMessage($summary, $modeLabel);
        } catch (\Throwable $e) {
            error_log('InformativoPublishNotifier::resendPushNotifications error: ' . $e->getMessage());

            return self::failResult('Erro ao reenviar push: ' . $e->getMessage());
        }
    }

    public static function notifyPublished(int $informativoId): void
    {
        if ($informativoId <= 0) {
            return;
        }

        try {
            $repo = new InformativosRepository();
            $informativo = $repo->getInformativoById($informativoId);

            if (!$informativo || empty($informativo['ativo'])) {
                return;
            }

            if (!self::isWithinPublicationWindow($informativo)) {
                return;
            }

            self::dispatchPush($informativoId, $informativo, false);
        } catch (\Throwable $e) {
            error_log('InformativoPublishNotifier::notifyPublished error: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $informativo
     * @return array{sent:int, skipped:int, failed:int}
     */
    private static function dispatchPush(int $informativoId, array $informativo, bool $forceResend, int $senderUserId = 0): array
    {
        $repo = new InformativosRepository();
        $departmentIds = $repo->getNotifyDepartmentsIds($informativoId);
        $authorId = (int) ($informativo['usuario_id'] ?? 0);
        $exclude = [];
        if ($authorId > 0) {
            $exclude[] = $authorId;
        }
        if ($senderUserId > 0 && $senderUserId !== $authorId) {
            $exclude[] = $senderUserId;
        }

        $userIds = ContentPublishRecipientsResolver::activeUserIds($departmentIds, $exclude);
        if ($userIds === []) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $titulo = trim((string) ($informativo['titulo'] ?? 'Novo comunicado'));
        $resumo = trim((string) ($informativo['resumo'] ?? ''));
        if ($resumo === '') {
            $plain = strip_tags((string) ($informativo['conteudo'] ?? ''));
            $resumo = mb_strlen($plain) > 200 ? mb_substr($plain, 0, 197) . '...' : $plain;
        }

        $urgente = !empty($informativo['urgente']);
        $pushTitle = $urgente ? 'Comunicado urgente' : 'Novo comunicado';
        $message = $titulo;
        if ($resumo !== '') {
            $message .= ' — ' . $resumo;
        }

        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $base . '/view-informativo/' . $informativoId;
        $scope = PublishPushDedupCache::scopeForEntity('informativo', $informativoId);

        /** @var string $link */
        /** @var bool $forceResend */
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
     * @param array<string, mixed> $informativo
     */
    public static function isWithinPublicationWindow(array $informativo): bool
    {
        $now = time();

        $publishAt = $informativo['publish_at'] ?? null;
        if ($publishAt !== null && $publishAt !== '') {
            $ts = strtotime((string) $publishAt);
            if ($ts !== false && $ts > $now) {
                return false;
            }
        }

        $expireAt = $informativo['expire_at'] ?? null;
        if ($expireAt !== null && $expireAt !== '') {
            $ts = strtotime((string) $expireAt);
            if ($ts !== false && $ts <= $now) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{sent:int, skipped:int, failed:int, no_subscription:int} $summary
     * @return array{success:bool, sent:int, skipped:int, failed:int, no_subscription:int, message:string}
     */
    private static function buildResultMessage(array $summary, string $modeLabel): array
    {
        $sent = (int) ($summary['sent'] ?? 0);
        $skipped = (int) ($summary['skipped'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        $noSub = (int) ($summary['no_subscription'] ?? 0);

        $parts = [];
        if ($skipped > 0) {
            $parts[] = $skipped . ' já recebeu';
        }
        if ($noSub > 0) {
            $parts[] = $noSub . ' sem inscrição push ativa';
        }
        if ($failed > 0) {
            $parts[] = $failed . ' com falha de entrega';
        }
        $detail = $parts !== [] ? ' ' . implode(', ', $parts) . '.' : '';

        if ($sent > 0) {
            $msg = sprintf('Push (%s) enviado para %d dispositivo(s).%s', $modeLabel, $sent, $detail);
        } else {
            $msg = sprintf('Nenhum push entregue (%s).%s', $modeLabel, $detail);
            if ($noSub > 0 && $sent === 0 && $failed === 0) {
                $msg .= ' Verifique se os colaboradores ativaram notificações no perfil/PWA.';
            }
        }

        return [
            'success' => $sent > 0,
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'no_subscription' => $noSub,
            'message' => $msg,
        ];
    }
}
