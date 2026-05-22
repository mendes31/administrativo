<?php

declare(strict_types=1);

namespace App\adms\Controllers\informativos;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativoPublishNotifier;

/**
 * Reenvio manual de notificações push PWA de um informativo (requer página ResendInformativoPush no nível de acesso).
 */
class ResendInformativoPush
{
    public function index(string|int $id = null): void
    {
        $informativoId = (int) ($id ?: ($_POST['id'] ?? 0));
        $redirect = ($_ENV['URL_ADM'] ?? '') . ($informativoId > 0 ? 'view-informativo/' . $informativoId : 'list-informativos');

        $perm = (new ButtonPermissionUserRepository())->buttonPermission(['ResendInformativoPush']);
        if (!is_array($perm) || !in_array('ResendInformativoPush', $perm, true)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Sem permissão para reenviar notificações push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        if ($informativoId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-informativos');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if ($csrf === '' || !CSRFHelper::validateCSRFToken('resend_informativo_push', $csrf)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token CSRF inválido ou expirado.</div>';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Use o botão na tela de visualização para reenviar o push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new InformativosRepository();
        if (!$repo->getInformativoById($informativoId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-informativos');
            exit;
        }

        $result = InformativoPublishNotifier::resendPushNotifications($informativoId);
        $class = !empty($result['success']) ? 'success' : 'warning';
        if (empty($result['success']) && (int) ($result['failed'] ?? 0) === 0) {
            $class = 'danger';
        }
        $_SESSION['msg'] = '<div class="alert alert-' . $class . '" role="alert">'
            . htmlspecialchars((string) ($result['message'] ?? ''), ENT_QUOTES, 'UTF-8')
            . '</div>';

        header('Location: ' . $redirect);
        exit;
    }
}
