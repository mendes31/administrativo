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
     * @return array{success:bool, sent:int, skipped:int, failed:int, message:string}
     */
    public static function resendPushNotifications(int $informativoId): array
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

            ContentPublishPushDispatcher::clearDedupForContent('informativo', $informativoId);
            $summary = self::dispatchPush($informativoId, $informativo, true);

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
    private static function dispatchPush(int $informativoId, array $informativo, bool $forceResend): array
    {
        $repo = new InformativosRepository();
        $departmentIds = $repo->getNotifyDepartmentsIds($informativoId);
        $authorId = (int) ($informativo['usuario_id'] ?? 0);
        $exclude = $authorId > 0 ? [$authorId] : [];

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
}
