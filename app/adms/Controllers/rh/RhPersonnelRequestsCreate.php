<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhPersonnelRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/** Cadastro de requisição de pessoal. */
class RhPersonnelRequestsCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = $_POST['form'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!CSRFHelper::validateCSRFToken('form_create_rh_personnel_request', $token)) {
                $_SESSION['error'] = 'Token de segurança inválido ou expirado.';
                $this->viewForm();
                return;
            }
            $this->store();
            return;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
        $posRepo = new \App\adms\Models\Repository\PositionsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        $this->data['positions'] = $posRepo->getAllPositions(1, 1000) ?: [];

        $pageElements = [
            'title_head' => 'Nova Requisição de Pessoal',
            'menu' => 'rh-personnel-requests',
            'buttonPermission' => ['RhPersonnelRequests', 'RhPersonnelRequestsCreate'],
        ];
        $layout = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $layout->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/rh/personnel_requests/create', $this->data))->loadView();
    }

    private function store(): void
    {
        $form = $_POST['form'] ?? [];
        $this->data['form'] = $form;

        if (trim((string) ($form['justificativa'] ?? '')) === '') {
            $_SESSION['error'] = 'Justificativa é obrigatória.';
            $this->viewForm();
            return;
        }

        try {
            $repo = new RhPersonnelRequestsRepository();
            $form['requester_id'] = (int) ($_SESSION['user_id'] ?? 0);
            $id = $repo->create($form);
            if ($id) {
                $_SESSION['success'] = 'Requisição cadastrada e enviada para aprovação.';
                header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
                return;
            }
            $_SESSION['error'] = 'Não foi possível cadastrar a requisição.';
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao cadastrar requisição de pessoal.', [
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro inesperado ao cadastrar requisição.';
        }

        $this->viewForm();
    }
}
