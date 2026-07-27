<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhEntrevistaAvaliadoresRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhEntrevistaAvaliadorConviteService;
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

            $form['avaliadores_adicionais'] = array_values(array_filter(
                array_map('intval', (array) ($form['avaliadores_adicionais'] ?? [])),
                static fn (int $v): bool => $v > 0
            ));

            try {
                $movimentacao = new \App\adms\Models\Services\RhCandidaturaMovimentacaoService();
                $id = $movimentacao->criarEntrevista($form);
                $actorId = (int) ($_SESSION['user_id'] ?? 0);
                $principalId = !empty($form['entrevistador_id']) ? (int) $form['entrevistador_id'] : null;
                try {
                    $toInvite = (new RhEntrevistaAvaliadoresRepository())->syncPainel(
                        (int) $id,
                        $principalId,
                        $form['avaliadores_adicionais'],
                        $actorId
                    );
                    if ($toInvite !== []) {
                        (new RhEntrevistaAvaliadorConviteService())->enviarConvites((int) $id, $toInvite, $actorId);
                    }
                } catch (\Throwable $e) {
                    GenerateLog::generateLog('error', 'Entrevista criada, mas painel de avaliadores falhou.', [
                        'entrevista_id' => (int) $id,
                        'error' => $e->getMessage(),
                    ]);
                    $_SESSION['error'] = 'Entrevista criada, porém o painel de avaliadores falhou: ' . $e->getMessage();
                    header("Location: {$_ENV['URL_ADM']}rh-entrevistas-view/$id");
                    return;
                }
                $_SESSION['success'] = "Entrevista cadastrada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}rh-entrevistas-view/$id");
                return;
            } catch (\Throwable $e) {
                $_SESSION['error'] = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Erro ao cadastrar entrevista.';
                $this->data['form'] = $form;
            }
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
