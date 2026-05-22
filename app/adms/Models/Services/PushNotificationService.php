<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\PushNotificationLog;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /**
     * @return array{success:bool, sent:int, failed:int, expired_ids:array<int,int>, errors:array<int,string>, details:array<int,array<string,mixed>>}
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $onlyEndpoint = null
    ): array {
        $result = $this->emptyResult();

        if ($userId <= 0) {
            $result['errors'][] = 'Usuário inválido.';
            return $result;
        }

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            $result['errors'][] = 'Push notifications desativadas ou VAPID incompleto.';
            return $result;
        }

        $subRepo = new PushSubscriptionRepository();
        $subscriptions = $subRepo->listByUserId($userId);
        if ($onlyEndpoint !== null && trim($onlyEndpoint) !== '') {
            $onlyEndpoint = trim($onlyEndpoint);
            $subscriptions = array_values(array_filter(
                $subscriptions,
                static fn(array $row): bool => (string) ($row['endpoint'] ?? '') === $onlyEndpoint
            ));
        }
        if ($subscriptions === []) {
            $result['errors'][] = $onlyEndpoint !== null
                ? 'Este navegador ainda não está sincronizado no servidor. Abra Meu Perfil neste aparelho, toque em Ativar notificações, e teste novamente.'
                : 'Usuário sem inscrição push neste dispositivo.';
            return $result;
        }

        $assets = $this->resolvePushAssets($icon, null);
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?: (rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/notificacoes'),
            'icon' => $assets['icon'],
            'badge' => $assets['badge'],
            'baseUrl' => $assets['baseUrl'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            $result['errors'][] = 'Falha ao montar payload JSON.';
            return $result;
        }

        try {
            $webPush = $this->createWebPush($configRepo->getConfig());
            $this->queueSubscriptions($webPush, $subscriptions, $payload);
            $this->processReports($webPush, $subRepo, $subscriptions, $result, $userId, 'send');
            $result['success'] = $result['sent'] > 0;
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            PushNotificationLog::log('error', 'Exceção no envio Web Push.', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Verifica inscrições no banco com ping silencioso (SW ignora `maintenance`).
     * Remove linhas com resposta 410/404 ou equivalente.
     *
     * @return array{checked:int, removed:int, failed:int, errors:array<int,string>}
     */
    public function pruneExpiredSubscriptions(int $batchSize = 200): array
    {
        $summary = [
            'checked' => 0,
            'removed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            $summary['errors'][] = 'Push desativado ou VAPID incompleto.';
            return $summary;
        }

        $subRepo = new PushSubscriptionRepository();
        $subscriptions = $subRepo->listAllForMaintenance($batchSize, 0);
        if ($subscriptions === []) {
            PushNotificationLog::log('info', 'Manutenção push: nenhuma inscrição no banco.');
            return $summary;
        }

        $payload = json_encode(['maintenance' => true], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            $summary['errors'][] = 'Falha ao montar payload de manutenção.';
            return $summary;
        }

        try {
            $webPush = $this->createWebPush($configRepo->getConfig());
            $this->queueSubscriptions($webPush, $subscriptions, $payload);

            $result = $this->emptyResult();
            $this->processReports($webPush, $subRepo, $subscriptions, $result, null, 'prune');

            $summary['checked'] = count($subscriptions);
            $summary['removed'] = count($result['expired_ids']);
            $summary['failed'] = $result['failed'];
            $summary['errors'] = $result['errors'];

            PushNotificationLog::log('info', 'Manutenção push concluída.', [
                'checked' => $summary['checked'],
                'removed' => $summary['removed'],
                'failed' => $summary['failed'],
                'total_in_db' => $subRepo->countAll(),
            ]);
        } catch (\Throwable $e) {
            $summary['errors'][] = $e->getMessage();
            PushNotificationLog::log('error', 'Exceção na manutenção push.', ['error' => $e->getMessage()]);
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createWebPush(array $config): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => (string) $config['vapid_subject'],
                'publicKey' => (string) $config['vapid_public_key'],
                'privateKey' => (string) $config['vapid_private_key'],
            ],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $subscriptions
     */
    private function queueSubscriptions(WebPush $webPush, array $subscriptions, string $payload): void
    {
        foreach ($subscriptions as $row) {
            $subscription = Subscription::create([
                'endpoint' => (string) $row['endpoint'],
                'keys' => [
                    'p256dh' => (string) $row['public_key'],
                    'auth' => (string) $row['auth_token'],
                ],
                'contentEncoding' => $this->resolveContentEncoding($row),
            ]);
            $webPush->queueNotification($subscription, $payload);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $subscriptions
     * @param array{success:bool, sent:int, failed:int, expired_ids:array<int,int>, errors:array<int,string>, details:array<int,array<string,mixed>>} $result
     */
    private function processReports(
        WebPush $webPush,
        PushSubscriptionRepository $subRepo,
        array $subscriptions,
        array &$result,
        ?int $userId,
        string $context
    ): void {
        $rowByEndpoint = [];
        foreach ($subscriptions as $row) {
            $rowByEndpoint[(string) ($row['endpoint'] ?? '')] = $row;
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            $endpointHash = hash('sha256', $endpoint);
            $row = $rowByEndpoint[$endpoint] ?? $subRepo->findByEndpointHash($endpointHash);
            $label = $row !== null ? $subRepo->getDeviceLabel($row) : 'Dispositivo';
            $rowUserId = $row !== null ? (int) ($row['user_id'] ?? 0) : ($userId ?? 0);
            $statusCode = $report->getResponse()?->getStatusCode();
            $expired = $this->shouldRemoveSubscriptionReport($report);

            if ($report->isSuccess()) {
                $result['sent']++;
                if ($context === 'send') {
                    $result['details'][] = [
                        'label' => $label,
                        'success' => true,
                        'error' => null,
                        'expired' => false,
                    ];
                }
                continue;
            }

            $reason = $report->getReason() ?: 'Falha desconhecida no envio push.';
            $result['failed']++;
            if ($context === 'send') {
                $result['errors'][] = $label . ': ' . $reason;
                $result['details'][] = [
                    'label' => $label,
                    'success' => false,
                    'error' => $reason,
                    'expired' => $expired,
                ];
            }

            if ($expired && $row !== null) {
                $subId = (int) ($row['id'] ?? 0);
                if ($subId > 0 && $subRepo->deleteById($subId)) {
                    $result['expired_ids'][] = $subId;
                    PushNotificationLog::log('info', 'Inscrição push inválida removida (410/404).', [
                        'context' => $context,
                        'subscription_id' => $subId,
                        'user_id' => $rowUserId,
                        'device' => $label,
                        'http_status' => $statusCode,
                        'endpoint' => mb_substr($endpoint, 0, 120),
                    ]);
                }
            } else {
                PushNotificationLog::log('warning', 'Falha no envio Web Push.', [
                    'context' => $context,
                    'user_id' => $rowUserId,
                    'device' => $label,
                    'http_status' => $statusCode,
                    'endpoint' => mb_substr($endpoint, 0, 120),
                    'reason' => $reason,
                    'will_remove' => false,
                ]);
            }
        }

        if ($context === 'send' && $result['expired_ids'] !== []) {
            PushNotificationLog::log('info', 'Limpeza automática após envio push.', [
                'user_id' => $userId,
                'removed_ids' => $result['expired_ids'],
                'count' => count($result['expired_ids']),
            ]);
        }
    }

    private function shouldRemoveSubscriptionReport(MessageSentReport $report): bool
    {
        if ($report->isSubscriptionExpired()) {
            return true;
        }

        $response = $report->getResponse();
        if ($response !== null) {
            $code = $response->getStatusCode();
            if ($code === 410 || $code === 404) {
                return true;
            }
        }

        $reason = strtolower($report->getReason() ?? '');
        foreach (['410', '404', 'gone', 'not found', 'unsubscribed', 'expired', 'no longer'] as $needle) {
            if (str_contains($reason, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success:bool, sent:int, failed:int, expired_ids:array<int,int>, errors:array<int,string>, details:array<int,array<string,mixed>>}
     */
    private function emptyResult(): array
    {
        return [
            'success' => false,
            'sent' => 0,
            'failed' => 0,
            'expired_ids' => [],
            'errors' => [],
            'details' => [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveContentEncoding(array $row): string
    {
        $endpoint = (string) ($row['endpoint'] ?? '');
        if (
            str_contains($endpoint, 'fcm.googleapis.com')
            || str_contains($endpoint, 'notify.windows.com')
        ) {
            return 'aes128gcm';
        }

        $stored = trim((string) ($row['content_encoding'] ?? ''));

        return $stored !== '' ? $stored : 'aes128gcm';
    }

    /**
     * @return array{icon:string, badge:string, baseUrl:string}
     */
    private function resolvePushAssets(?string $icon = null, ?string $badge = null): array
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');

        // Caminhos relativos: o service worker resolve pelo escopo do PWA (evita sino quando URL_ADM é IP interno).
        return [
            'icon' => $this->normalizePushAssetPath($icon, 'public/adms/image/pwa-icon-192.png'),
            'badge' => $this->normalizePushAssetPath($badge, 'public/adms/image/pwa-badge-192.png'),
            'baseUrl' => $base,
        ];
    }

    /**
     * Mantém caminho relativo no payload; URLs absolutas de outro host são descartadas no SW.
     */
    private function normalizePushAssetPath(?string $value, string $defaultRelative): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $defaultRelative;
        }

        if (preg_match('#^https?://#i', $value)) {
            $path = parse_url($value, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                return ltrim($path, '/');
            }

            return $defaultRelative;
        }

        return ltrim($value, '/');
    }

    /**
     * @return array{publicKey:string, privateKey:string}
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }
}
