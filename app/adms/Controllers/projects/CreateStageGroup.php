<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjStageGroupsRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class CreateStageGroup
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_stage_group', $this->data['form']['csrf_token'])) {
            $this->create();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head'       => 'Cadastrar Grupo de Etapas',
            'menu'             => 'list-stage-groups',
            'buttonPermission' => ['ListStageGroups'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge(
            $this->data ?? [],
            $pageLayoutService->configurePageElements($pageElements)
        );

        $loadView = new LoadViewService('adms/Views/projects/stage_groups/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];

        if (empty($form['name'])) {
            $errors['name'] = 'Nome do grupo é obrigatório.';
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $repo = new ProjStageGroupsRepository();
        $payload = [
            'name'        => trim($form['name']),
            'description' => $form['description'] ?? null,
            'active'      => !empty($form['active']) ? 1 : 0,
        ];

        $createdId = $repo->create($payload);

        if ($createdId) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            LogAlteracaoService::registrarAlteracao(
                'proj_stage_groups',
                (int)$createdId,
                $usuarioId,
                'insert',
                [],
                array_merge(['id' => (int)$createdId], $payload)
            );

            GenerateLog::generateLog('info', 'Grupo de etapas de projeto criado', ['id' => $createdId]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Grupo de etapas cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar grupo de etapas.</div>";
        $this->view();
    }
}

