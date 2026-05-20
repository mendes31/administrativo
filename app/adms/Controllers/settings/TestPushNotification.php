<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsPushConfigRepository;
use App\adms\Models\Repository\PushSubscriptionRepository;
use App\adms\Models\Services\PushNotificationService;

class TestPushNotification
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_push_test', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido. Atualize a página e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $configRepo = new AdmsPushConfigRepository();
        if (!$configRepo->isEnabled()) {
            $_SESSION['msg'] = 'Ative o push e configure VAPID antes de testar.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $testEndpoint = trim((string) ($_POST['test_endpoint'] ?? ''));
        $subRepo = new PushSubscriptionRepository();
        if ($testEndpoint === '' && !$subRepo->userHasSubscription($userId)) {
            $_SESSION['msg'] = 'Ative as notificações push em Meu Perfil neste dispositivo antes de testar.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
            exit;
        }

        $result = (new PushNotificationService())->sendToUser(
            $userId,
            'Teste — Portal Tiaraju',
            'Se você viu esta notificação, o Web Push está funcionando.',
            rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/notificacoes',
            null,
            $testEndpoint !== '' ? $testEndpoint : null
        );

        $totalDevices = $subRepo->countByUserId($userId);
        $details = $result['details'] ?? [];
        $detailLines = array_map(static function (array $item): string {
            $label = (string) ($item['label'] ?? 'Dispositivo');
            if (!empty($item['success'])) {
                return $label . ': enviado';
            }
            $err = (string) ($item['error'] ?? 'falhou');
            if (!empty($item['expired'])) {
                $err .= ' (inscrição expirada — reative em Meu Perfil)';
            }

            return $label . ': ' . $err;
        }, $details);

        if (!empty($result['success'])) {
            $sent = (int) ($result['sent'] ?? 0);
            $failed = (int) ($result['failed'] ?? 0);
            if ($testEndpoint !== '') {
                $_SESSION['msg'] = $detailLines !== []
                    ? implode(' | ', $detailLines)
                    : 'Notificação de teste enviada para este navegador.';
                $_SESSION['msg_type'] = $failed > 0 ? 'warning' : 'success';
            } elseif ($failed > 0) {
                $_SESSION['msg'] = $detailLines !== []
                    ? implode(' | ', $detailLines)
                    : "Enviado para {$sent} de {$totalDevices} dispositivo(s); falhou em {$failed}. Reative push em Meu Perfil nos aparelhos que não receberam.";
                $_SESSION['msg_type'] = 'warning';
            } else {
                $_SESSION['msg'] = $detailLines !== []
                    ? implode(' | ', $detailLines)
                    : ($sent > 1
                        ? "Notificação de teste enviada para {$sent} dispositivos."
                        : 'Notificação de teste enviada com sucesso.');
                $_SESSION['msg_type'] = 'success';
            }
        } else {
            $_SESSION['msg'] = $detailLines !== []
                ? implode(' | ', $detailLines)
                : ($result['errors'][0] ?? 'Falha ao enviar notificação de teste.');
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'push-config');
        exit;
    }
}
