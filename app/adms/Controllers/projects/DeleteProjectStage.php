<?php

namespace App\adms\Controllers\projects;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjStagesRepository;

class DeleteProjectStage
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (empty($this->data['form']['id']) || empty($this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Requisição inválida.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-project-stages');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_project_stage', $this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-project-stages');
            return;
        }

        $id = (int)$this->data['form']['id'];
        $repo = new ProjStagesRepository();
        $deleted = $repo->delete($id);

        if ($deleted) {
            GenerateLog::generateLog('info', 'Etapa de projeto apagada', ['id' => $id]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Etapa apagada com sucesso.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Não foi possível apagar a etapa.</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-project-stages');
    }
}

