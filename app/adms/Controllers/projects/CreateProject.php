<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\projects\ProjProjectsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateProject
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_create_project', $this->data['form']['csrf_token'])) {
            $this->create();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        // Carregar usuários ativos para selects de contato e responsável
        $usersRepo = new UsersRepository();
        $this->data['listUsers'] = $usersRepo->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Cadastrar Projeto',
            'menu' => 'list-projects',
            'buttonPermission' => ['ListProjects'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];

        if (empty($form['name'])) {
            $errors['name'] = 'Nome do projeto é obrigatório.';
        }

        if (empty($form['type']) || !in_array($form['type'], ['INTERNAL', 'EXTERNAL'], true)) {
            $form['type'] = 'INTERNAL';
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $repo = new ProjProjectsRepository();
        $createdId = $repo->create([
            'type' => $form['type'],
            'name' => trim($form['name']),
            'status' => $form['status'] ?? 'INICIADO',
            'start_date' => $form['start_date'] ?: null,
            'expected_end_date' => $form['expected_end_date'] ?: null,
            'end_date' => $form['end_date'] ?: null,
            'open_activities' => 0,
            'percent_complete' => 0,
            'pn_id' => null,
            'pn_code' => $form['pn_code'] ?? null,
            'pn_name' => $form['pn_name'] ?? null,
            'contact_user_id' => !empty($form['contact_user_id']) ? (int)$form['contact_user_id'] : null,
            'owner_user_id' => !empty($form['owner_user_id']) ? (int)$form['owner_user_id'] : null,
            'description' => $form['description'] ?? null,
            'active' => !empty($form['active']) ? 1 : 0,
        ]);

        if ($createdId) {
            GenerateLog::generateLog('info', 'Projeto criado', ['id' => $createdId]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Projeto cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar projeto.</div>";
        $this->view();
    }
}

