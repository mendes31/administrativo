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
            self::keepNotice('warning', 'Sem permissão para reenviar notificações push.');
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
                self::keepNotice('danger', 'Token CSRF inválido ou expirado. Atualize a página e tente de novo.');
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            self::keepNotice('danger', 'Use o botão na tela de visualização para reenviar o push.');
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
        if ($mode === 'notice') {
            $audience = trim((string) ($_POST['notice_audience'] ?? 'pending'));
            if (!in_array($audience, ['pending', 'all'], true)) {
                $audience = 'pending';
            }
            $result = CompanyEventPublishNotifier::sendAttendanceNotice(
                $eventId,
                (string) ($_POST['notice_text'] ?? ''),
                $audience
            );
        } else {
            $forceAll = ($mode !== 'pending');
            $senderUserId = (int) ($_SESSION['user_id'] ?? 0);
            $result = CompanyEventPublishNotifier::resendPushNotifications($eventId, $forceAll, $senderUserId);
        }
        $class = !empty($result['success']) ? 'success' : 'warning';
        if (empty($result['success']) && (int) ($result['failed'] ?? 0) === 0 && (int) ($result['sent'] ?? 0) === 0) {
            $class = ($mode === 'notice' && !empty($result['success'])) ? 'success' : 'danger';
        }
        if ($mode === 'notice' && !empty($result['success']) && (int) ($result['sent'] ?? 0) === 0) {
            $class = 'warning';
        }
        self::keepNotice($class, (string) ($result['message'] ?? ''));

        header('Location: ' . $redirect);
        exit;
    }

    /**
     * A chave padrão de sessão é apagada pelo layout antes da tela desenhar.
     * Este aviso fica numa chave própria para o resultado aparecer na ficha.
     */
    private static function keepNotice(string $type, string $message): void
    {
        if (!in_array($type, ['success', 'warning', 'danger', 'info'], true)) {
            $type = 'info';
        }
        $_SESSION['company_event_action_notice'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
