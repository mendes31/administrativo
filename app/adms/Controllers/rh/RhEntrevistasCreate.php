<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;

class RhEntrevistasCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $candidatoIdPreSelect = isset($_GET['candidato_id']) ? (int)$_GET['candidato_id'] : 0;
        $this->data['form'] = $_POST['form'] ?? [];
        if ($candidatoIdPreSelect > 0 && empty($this->data['form']['rh_candidato_id'])) {
            $this->data['form']['rh_candidato_id'] = $candidatoIdPreSelect;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!CSRFHelper::validateCSRFToken('form_create_rh_entrevista', $csrfToken)) {
                $_SESSION['error'] = "Token de segurança inválido ou expirado. Recarregue a página e tente novamente.";
                $this->viewForm();
                return;
            }

            $form = $_POST['form'] ?? [];
            if (empty($form['rh_candidato_id']) || empty($form['data_hora'])) {
                $_SESSION['error'] = "Candidato e data/hora são obrigatórios.";
                $this->data['form'] = $form;
                $this->viewForm();
                return;
            }

            $entrevistaAuth = [
                'rh_candidato_id' => (int) ($form['rh_candidato_id'] ?? 0),
                'rh_vaga_id' => (int) ($form['rh_vaga_id'] ?? 0),
            ];
            if (!\App\adms\Models\Services\RhPermissionService::canManageEntrevista($entrevistaAuth)) {
                $_SESSION['error'] = 'Você não tem permissão para agendar entrevista neste contexto.';
                $this->data['form'] = $form;
                $this->viewForm();
                return;
            }

            $repo = new RhEntrevistasRepository();
            $id = $repo->create($form);
            if ($id) {
                $_SESSION['success'] = "Entrevista cadastrada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-entrevistas-view/$id");
                return;
            }
            $_SESSION['error'] = "Erro ao cadastrar entrevista.";
            $this->data['form'] = $form;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $candRepo = new RhCandidatosRepository();
        $vagaRepo = new RhVagasRepository();
        $userRepo = new \App\adms\Models\Repository\UsersRepository();

        $candidatos = $candRepo->getAll([], 1, 1000);
        $vagas = $vagaRepo->getAll([], 1, 1000);

        $this->data['candidatos'] = $candidatos['data'] ?? [];
        $this->data['vagas'] = $vagas['data'] ?? [];
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];

        $pageElements = [
            'title_head' => 'Cadastrar Entrevista',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => ['RhEntrevistas', 'RhEntrevistasCreate'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/create', $this->data);
        $loadView->loadView();
    }
}
