<?php

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Services\EmployeeRequestWorkflowService;

/**
 * Controller para gestor aprovar/rejeitar solicitações (imediato, delegado ou escalado).
 */
class ApproveEmployeeRequestManager
{
    public function index(int|string $id): void
    {
        if (!(int) $id) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-employee-requests");
            return;
        }

        $repository = new EmployeeRequestsRepository();
        $request = $repository->getById((int) $id);

        if (!$request) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-employee-requests");
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $workflow = new EmployeeRequestWorkflowService();

        if (!$workflow->canActAsManager($request, $userId)) {
            $_SESSION['error'] = 'Você não tem permissão para aprovar esta solicitação.';
            header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
            return;
        }

        if ($request['status'] !== 'pending_manager_approval') {
            $_SESSION['error'] = 'Esta solicitação não está aguardando aprovação do gestor.';
            header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if (!CSRFHelper::validateCSRFToken('form_approve_request_manager', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
                header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
                return;
            }

            if ($action === 'approve') {
                $result = $workflow->approveManagerStep((int) $id, $userId);
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">'
                        . htmlspecialchars($result['message'])
                        . '</div>';
                    GenerateLog::generateLog('info', 'Solicitação aprovada pelo gestor.', [
                        'request_id' => $id,
                        'manager_id' => $userId,
                        'via_delegation' => $result['via_delegation'],
                    ]);
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? '');
                if ($reason === '') {
                    $_SESSION['error'] = 'Motivo da rejeição é obrigatório.';
                    header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
                    return;
                }

                $result = $workflow->rejectManagerStep((int) $id, $userId, $reason);
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">'
                        . htmlspecialchars($result['message'])
                        . '</div>';
                    GenerateLog::generateLog('info', 'Solicitação rejeitada pelo gestor.', [
                        'request_id' => $id,
                        'manager_id' => $userId,
                        'reason' => $reason,
                    ]);
                } else {
                    $_SESSION['error'] = $result['message'];
                }
            }
        }

        header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
    }
}
