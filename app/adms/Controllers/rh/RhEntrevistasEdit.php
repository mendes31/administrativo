<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;

class RhEntrevistasEdit
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        $repo = new RhEntrevistasRepository();
        $entrevista = $repo->getById($id);
        if (!$entrevista) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        if (!\App\adms\Models\Services\RhPermissionService::canManageEntrevista($entrevista)) {
            $_SESSION['error'] = 'Você não tem permissão para editar esta entrevista.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-entrevistas');
            return;
        }

        $this->data['entrevista'] = $entrevista;
        $this->data['form'] = $_POST['form'] ?? [
            'rh_candidato_id'   => $entrevista['rh_candidato_id'],
            'rh_vaga_id'        => $entrevista['rh_vaga_id'] ?? '',
            'tipo'              => $entrevista['tipo'] ?? 'presencial',
            'entrevistador_id'  => $entrevista['entrevistador_id'] ?? '',
            'data_hora'         => $entrevista['data_hora'] ?? '',
            'local'             => $entrevista['local'] ?? '',
            'observacoes'       => $entrevista['observacoes'] ?? '',
            'resultado'         => $entrevista['resultado'] ?? '',
            'feedback'          => $entrevista['feedback'] ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!CSRFHelper::validateCSRFToken('form_edit_rh_entrevista', $csrfToken)) {
                $_SESSION['error'] = "Token de segurança inválido ou expirado.";
                $this->viewForm($id);
                return;
            }
            $form = $_POST['form'] ?? [];
            if (empty($form['data_hora'])) {
                $_SESSION['error'] = "Data/hora é obrigatória.";
                $this->data['form'] = $form;
                $this->viewForm($id);
                return;
            }
            // Ao agendar (preencher data/hora) com entrevista ainda pendente, mudar para "agendado"
            $resultadoAtual = $entrevista['resultado'] ?? '';
            $resultadoForm = trim($form['resultado'] ?? '');
            if (($resultadoAtual === '' || $resultadoAtual === 'pendente') && $resultadoForm !== 'aprovado' && $resultadoForm !== 'reprovado') {
                $form['resultado'] = 'agendado';
            }
            if ($repo->update($id, $form)) {
                // Refletir resultado da entrevista no pipeline (mesma autorização já validada)
                $candidatoId = (int) ($entrevista['rh_candidato_id'] ?? 0);
                $vagaId = (int) ($entrevista['rh_vaga_id'] ?? 0);
                if ($candidatoId > 0 && $vagaId > 0 && in_array($resultadoForm, ['aprovado', 'reprovado'], true)) {
                    $vagaRepo = new RhVagasRepository();
                    $vagaRepo->atualizarStatusVinculo(
                        $vagaId,
                        $candidatoId,
                        $resultadoForm,
                        null,
                        \App\adms\Models\Repository\RhCandidaturaHistoricoRepository::ORIGEM_ENTREVISTA,
                        $id,
                        \App\adms\Models\Services\RhCandidaturaMotivoCatalog::forEntrevistaResultado($resultadoForm)
                    );
                }
                $_SESSION['success'] = "Entrevista atualizada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-entrevistas-view/$id");
                return;
            }
            $_SESSION['error'] = "Erro ao atualizar entrevista.";
            $this->data['form'] = $form;
        }

        $this->viewForm($id);
    }

    private function viewForm(int $id): void
    {
        $candRepo = new RhCandidatosRepository();
        $vagaRepo = new RhVagasRepository();
        $userRepo = new \App\adms\Models\Repository\UsersRepository();

        $this->data['candidatos'] = ($candRepo->getAll([], 1, 1000))['data'] ?? [];
        $this->data['vagas'] = ($vagaRepo->getAll([], 1, 1000))['data'] ?? [];
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];

        $pageElements = [
            'title_head' => 'Editar Entrevista',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => ['RhEntrevistas', 'RhEntrevistasEdit'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/edit', $this->data);
        $loadView->loadView();
    }
}
