<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar tipo de solicitação
 */
class CreateRequestType
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $pageElements = [
            'title_head' => 'Criar Tipo de Solicitação',
            'menu' => 'list-request-types',
            'buttonPermission' => [
                'ListRequestTypes',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/create_request_type', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_create_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-request-type');
            exit;
        }

        $data = [
            'code' => strtolower(trim($_POST['code'] ?? '')),
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
        if (empty($data['code'])) {
            $_SESSION['error'] = 'Código é obrigatório.';
            return;
        }

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        // Validar formato do código (apenas letras, números e underscore)
        if (!preg_match('/^[a-z0-9_]+$/', $data['code'])) {
            $_SESSION['error'] = 'Código deve conter apenas letras minúsculas, números e underscore.';
            return;
        }

        // Verificar se código já existe
        $repository = new RequestTypesRepository();
        $existing = $repository->getByCode($data['code']);
        if ($existing) {
            $_SESSION['error'] = 'Este código já está em uso.';
            return;
        }

        if ($repository->create($data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de solicitação criado com sucesso!</div>';
            GenerateLog::generateLog("info", "Tipo de solicitação criado.", $data);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-request-types');
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao criar tipo de solicitação. Tente novamente.';
        }
    }
}

