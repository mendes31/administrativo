<?php

namespace App\adms\Controllers\projects;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjProjectsRepository;
use App\adms\Models\Repository\projects\ProjProjectStagesRepository;
use App\adms\Models\Services\LogAlteracaoService;

class DeleteProject
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (empty($this->data['form']['id']) || empty($this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Requisição inválida.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_project', $this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        $id = (int)$this->data['form']['id'];
        if ($id <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Projeto inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        $projectsRepo = new ProjProjectsRepository();
        $stagesRepo   = new ProjProjectStagesRepository();

        // Captura o estado atual do projeto e de suas etapas para log de alterações
        $projetoAntes = $projectsRepo->getOne($id) ?: [];
        $etapasAntes  = $stagesRepo->getByProject($id);

        $deleted = $projectsRepo->delete($id);

        if ($deleted) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);

            // Log detalhado do projeto removido
            LogAlteracaoService::registrarAlteracao(
                'proj_projects',
                $id,
                $usuarioId,
                'delete',
                $projetoAntes,
                []
            );

            // Log resumido das etapas vinculadas removidas em cascata
            if (!empty($etapasAntes)) {
                LogAlteracaoService::registrarAlteracao(
                    'proj_project_stages',
                    $id,
                    $usuarioId,
                    'delete',
                    ['etapas_antes' => json_encode($etapasAntes)],
                    []
                );
            }

            GenerateLog::generateLog('info', 'Projeto apagado', ['id' => $id]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Projeto apagado com sucesso.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Não foi possível apagar o projeto.</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
    }
}

