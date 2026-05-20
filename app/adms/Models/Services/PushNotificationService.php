<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /**
     * @return array{success:bool, sent:int, failed:int, expired_ids:array<int,int>, errors:array<int,string>}
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        ?string $url = null,
        ?string $icon = null,
        ?string $onlyEndpoint = null
    ): array {
        $result = [
            'success' => false,
            'sent' => 0,
            'failed' => 0,
            'expired_ids' => [],
            'errors' => [],
            'details' => [],
        ];

        if ($userId <= 0) {
            $result['errors'][] = 'Usuário inválido.';
            return $result;
        }

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            $result['errors'][] = 'Push notifications desativadas ou VAPID incompleto.';
            return $result;
        }

        $config = $configRepo->getConfig();
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
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) $config['vapid_subject'],
                    'publicKey' => (string) $config['vapid_public_key'],
                    'privateKey' => (string) $config['vapid_private_key'],
                ],
            ]);

            $rowByEndpoint = [];
            foreach ($subscriptions as $row) {
                $rowByEndpoint[(string) ($row['endpoint'] ?? '')] = $row;
            }

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

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getEndpoint();
                $endpointHash = hash('sha256', $endpoint);
                $row = $rowByEndpoint[$endpoint] ?? $subRepo->findByEndpointHash($endpointHash);
                $label = $row !== null ? $subRepo->getDeviceLabel($row) : 'Dispositivo';

                if ($report->isSuccess()) {
                    $result['sent']++;
                    $result['details'][] = [
                        'label' => $label,
                        'success' => true,
                        'error' => null,
                        'expired' => false,
                    ];
                    continue;
                }

                $reason = $report->getReason() ?: 'Falha desconhecida no envio push.';
                $result['failed']++;
                $result['errors'][] = $label . ': ' . $reason;
                $result['details'][] = [
                    'label' => $label,
                    'success' => false,
                    'error' => $reason,
                    'expired' => $report->isSubscriptionExpired(),
                ];

                if ($report->isSubscriptionExpired() && $row !== null) {
                    $subId = (int) ($row['id'] ?? 0);
                    if ($subId > 0) {
                        $subRepo->deleteById($subId);
                        $result['expired_ids'][] = $subId;
                    }
                }

                \App\adms\Helpers\GenerateLog::generateLog('warning', 'Falha no envio Web Push.', [
                    'user_id' => $userId,
                    'device' => $label,
                    'endpoint' => mb_substr($endpoint, 0, 120),
                    'reason' => $reason,
                    'expired' => $report->isSubscriptionExpired(),
                ]);
            }

            $result['success'] = $result['sent'] > 0;
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * FCM (Chrome/Edge) e push services modernos usam aes128gcm.
     *
     * @param array<string, mixed> $row
     */
    private function resolveContentEncoding(array $row): string
    {
        $endpoint = (string) ($row['endpoint'] ?? '');
        if (
            str_contains($endpoint, 'fcm.googleapis.com')
            || str_contains($endpoint, 'mozilla.com')
            || str_contains($endpoint, 'windows.com')
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

        return [
            'icon' => $icon ?: ($base . '/public/adms/image/pwa-icon-192.png'),
            'badge' => $badge ?: ($base . '/public/adms/image/pwa-badge-192.png'),
            'baseUrl' => $base,
        ];
    }

    /**
     * @return array{publicKey:string, privateKey:string}
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }
}
