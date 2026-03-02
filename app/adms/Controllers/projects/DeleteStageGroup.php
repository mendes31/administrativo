<?php

namespace App\adms\Controllers\projects;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjStageGroupsRepository;
use App\adms\Models\Services\LogAlteracaoService;

class DeleteStageGroup
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (empty($this->data['form']['id']) || empty($this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Requisição inválida.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_stage_group', $this->data['form']['csrf_token'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        $id = (int)$this->data['form']['id'];
        $repo = new ProjStageGroupsRepository();

        $antes = $repo->getOne($id);
        $deleted = $repo->delete($id);

        if ($deleted) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            LogAlteracaoService::registrarAlteracao(
                'proj_stage_groups',
                $id,
                $usuarioId,
                'delete',
                $antes ?: [],
                []
            );

            GenerateLog::generateLog('info', 'Grupo de etapas de projeto apagado', ['id' => $id]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Grupo de etapas apagado com sucesso.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Não foi possível apagar o grupo de etapas.</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
    }
}

