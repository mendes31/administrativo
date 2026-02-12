<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;

class RhVagasCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = $_POST['form'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        // Carregar departamentos, cargos e usuários para selects
        $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
        $posRepo = new \App\adms\Models\Repository\PositionsRepository();
        $userRepo = new \App\adms\Models\Repository\UsersRepository();

        $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        $this->data['positions'] = $posRepo->getAllPositions(1, 1000) ?: [];
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];

        $pageElements = [
            'title_head' => 'Cadastrar Vaga',
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagas', 'RhVagasCreate'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/create', $this->data);
        $loadView->loadView();
    }

    private function store(): void
    {
        $form = $_POST['form'] ?? [];
        $this->data['form'] = $form;

        $titulo = trim($form['titulo'] ?? '');
        if ($titulo === '') {
            $_SESSION['error'] = "Título da vaga é obrigatório.";
            $this->viewForm();
            return;
        }

        try {
            $repo = new RhVagasRepository();
            $id = $repo->create($form);

            if ($id) {
                $_SESSION['success'] = "Vaga cadastrada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-vagas-view/{$id}");
                return;
            }

            $_SESSION['error'] = "Erro: Vaga não foi cadastrada.";
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao cadastrar vaga.', [
                'form'  => $form,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = "Erro inesperado ao cadastrar vaga.";
        }

        $this->viewForm();
    }
}

