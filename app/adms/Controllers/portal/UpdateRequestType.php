<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar tipo de solicitação
 */
class UpdateRequestType
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        $repository = new RequestTypesRepository();
        $this->data['requestType'] = $repository->getById((int)$id);

        if (!$this->data['requestType']) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        $pageElements = [
            'title_head' => 'Editar Tipo de Solicitação',
            'menu' => 'list-request-types',
            'buttonPermission' => [
                'ListRequestTypes',
                'ViewRequestType',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/update_request_type', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_update_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requires_manager_approval' => isset($_POST['requires_manager_approval']) && $_POST['requires_manager_approval'] === '1',
            'requires_dates' => isset($_POST['requires_dates']) && $_POST['requires_dates'] === '1',
            'requires_days' => isset($_POST['requires_days']) && $_POST['requires_days'] === '1',
            'requires_amount' => isset($_POST['requires_amount']) && $_POST['requires_amount'] === '1',
            'icon' => trim($_POST['icon'] ?? ''),
            'color' => $_POST['color'] ?? 'primary',
            'status' => isset($_POST['status']) && $_POST['status'] === '1',
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        $repository = new RequestTypesRepository();
        
        if ($repository->update($id, $data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação atualizado com sucesso!</div>';
            GenerateLog::generateLog("info", "Tipo de solicitação atualizado.", ['id' => $id, 'data' => $data]);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-request-types');
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar tipo de solicitação. Tente novamente.';
        }
    }
}

