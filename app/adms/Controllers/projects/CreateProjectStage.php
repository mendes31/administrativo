<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjStagesRepository;
use App\adms\Views\Services\LoadViewService;

class CreateProjectStage
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_project_stage', $this->data['form']['csrf_token'])) {
            $this->create();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Etapa de Projeto',
            'menu' => 'list-projects',
            'buttonPermission' => ['ListProjectStages'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/stages/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];

        if (empty($form['name'])) {
            $errors['name'] = 'Nome é obrigatório.';
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $repo = new ProjStagesRepository();
        $createdId = $repo->create([
            'name' => trim($form['name']),
            'description' => $form['description'] ?? null,
            'sequence_default' => (int)($form['sequence_default'] ?? 1),
            'is_cost_stage' => !empty($form['is_cost_stage']) ? 1 : 0,
            'active' => !empty($form['active']) ? 1 : 0,
        ]);

        if ($createdId) {
            GenerateLog::generateLog('info', 'Etapa de projeto criada', ['id' => $createdId]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Etapa cadastrada com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-project-stages');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar etapa.</div>";
        $this->view();
    }
}

