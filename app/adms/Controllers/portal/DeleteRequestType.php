<?php

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RequestTypesRepository;

/**
 * Controller para deletar tipo de solicitação
 */
class DeleteRequestType
{
    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_delete_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        // Verificar se há solicitações usando este tipo
        $repository = new RequestTypesRepository();
        $requestType = $repository->getById((int)$id);
        
        if (!$requestType) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        // Verificar se há solicitações usando este tipo
        $requestsRepo = new \App\adms\Models\Repository\EmployeeRequestsRepository();
        $requests = $requestsRepo->getAll(['request_type' => $requestType['code']], 1, 1);
        
        if (!empty($requests)) {
            $_SESSION['error'] = 'Não é possível excluir este tipo pois existem solicitações usando-o. Desative-o ao invés de excluir.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        if ($repository->delete((int)$id)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação excluído com sucesso!</div>';
            GenerateLog::generateLog("info", "Tipo de solicitação excluído.", ['id' => $id]);
        } else {
            $_SESSION['error'] = 'Erro ao excluir tipo de solicitação. Tente novamente.';
        }
        
        header("Location: {$_ENV['URL_ADM']}list-request-types");
    }
}

