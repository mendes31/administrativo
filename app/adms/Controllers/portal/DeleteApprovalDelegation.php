<?php

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ApprovalDelegationsRepository;

class DeleteApprovalDelegation
{
    public function index(int|string $id): void
    {
        $delegationId = (int) $id;
        if ($delegationId <= 0) {
            $_SESSION['error'] = 'Delegação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST'
            || !CSRFHelper::validateCSRFToken('form_delete_approval_delegation', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
            exit;
        }

        $repo = new ApprovalDelegationsRepository();
        $row = $repo->getById($delegationId);
        if (!$row) {
            $_SESSION['error'] = 'Delegação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
            exit;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $isFull = UserAccessHelper::hasFullSystemAccess();
        if (!$isFull
            && (int) $row['delegator_user_id'] !== $userId
            && (int) ($row['created_by'] ?? 0) !== $userId) {
            $_SESSION['error'] = 'Sem permissão para excluir esta delegação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
            exit;
        }

        if ($repo->delete($delegationId)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Delegação removida.</div>';
        } else {
            $_SESSION['error'] = 'Erro ao remover delegação.';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
        exit;
    }
}
