<?php

declare(strict_types=1);

namespace App\adms\Controllers\companyEvents;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Services\CompanyEventPublishNotifier;

class ResendCompanyEventPush
{
    public function index(string|int $id = null): void
    {
        $eventId = (int) ($id ?: ($_POST['id'] ?? 0));
        $redirect = ($_ENV['URL_ADM'] ?? '') . ($eventId > 0 ? 'view-company-event/' . $eventId : 'list-company-events');

        $perm = (new ButtonPermissionUserRepository())->buttonPermission(['ResendCompanyEventPush']);
        if (!is_array($perm) || !in_array('ResendCompanyEventPush', $perm, true)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Sem permissão para reenviar notificações push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        if ($eventId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do evento não informado.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-company-events');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if ($csrf === '' || !CSRFHelper::validateCSRFToken('resend_company_event_push', $csrf)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token CSRF inválido ou expirado.</div>';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Use o botão na tela de visualização para reenviar o push.</div>';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new CompanyEventsRepository();
        if (!$repo->getById($eventId)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Evento não encontrado.</div>';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-company-events');
            exit;
        }

        $mode = trim((string) ($_POST['mode'] ?? 'all'));
        $forceAll = ($mode !== 'pending');

        $result = CompanyEventPublishNotifier::resendPushNotifications($eventId, $forceAll);
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
