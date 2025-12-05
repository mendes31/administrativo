<?php

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Controller para gestor aprovar/rejeitar solicitações
 */
class ApproveEmployeeRequestManager
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

        // Verificar se o usuário é o gestor do colaborador
        $userId = $_SESSION['user_id'] ?? 0;
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        if (!$isSuperAdmin && $request['immediate_supervisor_id'] != $userId) {
            $_SESSION['error'] = 'Você não tem permissão para aprovar esta solicitação.';
            header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
            return;
        }

        // Verificar se está no status correto
        if ($request['status'] !== 'pending_manager_approval') {
            $_SESSION['error'] = 'Esta solicitação não está aguardando aprovação do gestor.';
            header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_approve_request_manager', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
                header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
                return;
            }

            if ($action === 'approve') {
                if ($repository->approveByManager((int)$id, $userId)) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação aprovada pelo gestor! Agora aguarda aprovação do RH.</div>';
                    GenerateLog::generateLog("info", "Solicitação aprovada pelo gestor.", ['request_id' => $id, 'manager_id' => $userId]);
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

                if ($repository->rejectByManager((int)$id, $userId, $reason)) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Solicitação rejeitada pelo gestor.</div>';
                    GenerateLog::generateLog("info", "Solicitação rejeitada pelo gestor.", ['request_id' => $id, 'manager_id' => $userId, 'reason' => $reason]);
                } else {
                    $_SESSION['error'] = 'Erro ao rejeitar solicitação.';
                }
            }
        }

        header("Location: {$_ENV['URL_ADM']}view-employee-request/{$id}");
    }
}

