<?php

declare(strict_types=1);

namespace App\adms\Controllers\push;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;

class PushSubscribe
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        $configRepo = new AdmsPushConfigRepository();
        $config = $configRepo->getConfig();
        $publicKey = $configRepo->getPublicKey();

        $subRepo = new PushSubscriptionRepository();
        $endpoint = trim((string) ($_GET['endpoint'] ?? ''));

        echo json_encode([
            'success' => true,
            'enabled' => $configRepo->isEnabled(),
            'configured' => $publicKey !== null && trim((string) ($config['vapid_subject'] ?? '')) !== '',
            'publicKey' => $publicKey,
            'subscribed' => $subRepo->userHasSubscription($userId),
            'subscriptionCount' => $subRepo->countByUserId($userId),
            'endpointRegistered' => $endpoint !== '' ? $subRepo->hasEndpointForUser($userId, $endpoint) : null,
            'devices' => $subRepo->listDevicesForUser($userId),
            'supported' => true,
        ]);
    }

    public function subscribe(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_subscribe', (string) ($payload['csrf_token'] ?? ''))) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Notificações push não estão ativas no sistema.']);
            return;
        }

        $subscription = $payload['subscription'] ?? $payload;
        if (!is_array($subscription)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Subscription inválida']);
            return;
        }

        $ok = (new PushSubscriptionRepository())->upsert($userId, $subscription);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível salvar a inscrição push.']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Notificações push ativadas neste dispositivo.']);
    }

    public function unsubscribe(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_subscribe', (string) ($payload['csrf_token'] ?? ''))) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        if ($endpoint === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Endpoint não informado']);
            return;
        }

        $ok = (new PushSubscriptionRepository())->deleteByEndpoint($userId, $endpoint);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível remover a inscrição push.']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Notificações push desativadas neste dispositivo.']);
    }
}
