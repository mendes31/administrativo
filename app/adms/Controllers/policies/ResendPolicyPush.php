<?php

declare(strict_types=1);

namespace App\adms\Controllers\policies;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\PolicyPublishNotifier;

class ResendPolicyPush
{
    public function index(string $id = ''): void
    {
        $policyId = (int) ($id ?: ($_POST['id'] ?? 0));
        $redirect = ($_ENV['URL_ADM'] ?? '') . ($policyId > 0 ? 'view-policy/' . $policyId : 'list-policies');

        $perm = (new ButtonPermissionUserRepository())->buttonPermission(['ResendPolicyPush']);
        if (!is_array($perm) || !in_array('ResendPolicyPush', $perm, true)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Sem permissão para reenviar notificações push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        if ($policyId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da política não informado.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-policies');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if ($csrf === '' || !CSRFHelper::validateCSRFToken('resend_policy_push', $csrf)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token CSRF inválido ou expirado.</div>';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Use o botão na tela de visualização para reenviar o push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new PoliciesRepository();
        if (!$repo->getPolicyById($policyId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Política não encontrada.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-policies');
            exit;
        }

        $result = PolicyPublishNotifier::resendPushNotifications($policyId);
        $class = !empty($result['success']) ? 'success' : 'danger';
        $_SESSION['msg'] = '<div class="alert alert-' . $class . '" role="alert">'
            . htmlspecialchars((string) ($result['message'] ?? ''), ENT_QUOTES, 'UTF-8')
            . '</div>';

        header('Location: ' . $redirect);
        exit;
    }
}
