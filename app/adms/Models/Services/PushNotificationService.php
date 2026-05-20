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
        $subscriptions = (new PushSubscriptionRepository())->listByUserId($userId);
        if ($onlyEndpoint !== null && trim($onlyEndpoint) !== '') {
            $onlyEndpoint = trim($onlyEndpoint);
            $subscriptions = array_values(array_filter(
                $subscriptions,
                static fn(array $row): bool => (string) ($row['endpoint'] ?? '') === $onlyEndpoint
            ));
        }
        if ($subscriptions === []) {
            $result['errors'][] = $onlyEndpoint !== null
                ? 'Nenhuma inscrição push encontrada para este navegador. Ative em Meu Perfil neste dispositivo.'
                : 'Usuário sem inscrição push neste dispositivo.';
            return $result;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?: (rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/notificacoes'),
            'icon' => $icon ?: (rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/public/adms/uploads/users/1/pwa-icon-512.png'),
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

            foreach ($subscriptions as $row) {
                $subscription = Subscription::create([
                    'endpoint' => (string) $row['endpoint'],
                    'keys' => [
                        'p256dh' => (string) $row['public_key'],
                        'auth' => (string) $row['auth_token'],
                    ],
                    'contentEncoding' => (string) ($row['content_encoding'] ?? 'aes128gcm'),
                ]);
                $webPush->queueNotification($subscription, $payload);
            }

            $subRepo = new PushSubscriptionRepository();
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getEndpoint();
                $endpointHash = hash('sha256', $endpoint);
                $row = $subRepo->findByEndpointHash($endpointHash);

                if ($report->isSuccess()) {
                    $result['sent']++;
                    continue;
                }

                $result['failed']++;
                $result['errors'][] = $report->getReason() ?: 'Falha desconhecida no envio push.';

                if ($report->isSubscriptionExpired() && $row !== null) {
                    $subId = (int) ($row['id'] ?? 0);
                    if ($subId > 0) {
                        $subRepo->deleteById($subId);
                        $result['expired_ids'][] = $subId;
                    }
                }
            }

            $result['success'] = $result['sent'] > 0;
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @return array{publicKey:string, privateKey:string}
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }
}
