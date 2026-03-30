<?php

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeeRequestsRepository;

/**
 * Controller para RH aprovar/rejeitar solicitações
 */
class ApproveEmployeeRequestHR
{
    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-employee-requests");
            return;
        }

        $repository = new EmployeeRequestsRepository();
        $request = $repository->getById((int)$id);

        if (!$request) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-employee-requests");
            return;
        }

        // Verificar se o usuário tem permissão (super admin ou RH)
        $userId = $_SESSION['user_id'] ?? 0;
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        // TODO: Adicionar verificação de permissão específica para RH
        if (!$isSuperAdmin) {
            // Por enquanto, apenas super admin pode aprovar como RH
            // Pode ser melhorado depois com permissões específicas
        }

        // Verificar se está no status correto
        if ($request['status'] !== 'pending_hr_approval') {
            $_SESSION['error'] = 'Esta solicitação não está aguardando aprovação do RH.';
            header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_approve_request_hr', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
                header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
                return;
            }

            if ($action === 'approve') {
                if ($repository->approveByHR((int)$id, $userId)) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação aprovada pelo RH!</div>';
                    GenerateLog::generateLog("info", "Solicitação aprovada pelo RH.", ['request_id' => $id, 'hr_id' => $userId]);
                } else {
                    $_SESSION['error'] = 'Erro ao aprovar solicitação.';
                }
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? '');
                if (empty($reason)) {
                    $_SESSION['error'] = 'Motivo da rejeição é obrigatório.';
                    header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
                    return;
                }

                if ($repository->rejectByHR((int)$id, $userId, $reason)) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Solicitação rejeitada pelo RH.</div>';
                    GenerateLog::generateLog("info", "Solicitação rejeitada pelo RH.", ['request_id' => $id, 'hr_id' => $userId, 'reason' => $reason]);
                } else {
                    $_SESSION['error'] = 'Erro ao rejeitar solicitação.';
                }
            }
        }

        header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
    }
}

