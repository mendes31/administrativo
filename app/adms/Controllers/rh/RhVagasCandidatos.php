<?php

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\RhPermissionService;

class RhVagasCandidatos
{
    private array|string|null $data = null;

    public function index(int $vagaId): void
    {
        if (!RhPermissionService::canManagePipelineByVagaId($vagaId)) {
            $_SESSION['error'] = 'Você não tem permissão para gerenciar candidatos desta vaga.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas-view/' . $vagaId);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveVagaCandidatos($vagaId);
        }

        $this->loadVagaCandidatosData($vagaId);
        $this->loadView();
    }

    private function saveVagaCandidatos(int $vagaId): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_rh_vincular_candidato_vaga', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas-candidatos/' . $vagaId);
            exit;
        }

        if (!RhPermissionService::canManagePipelineByVagaId($vagaId)) {
            $_SESSION['error'] = 'Você não tem permissão para gerenciar candidatos desta vaga.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas-view/' . $vagaId);
            exit;
        }

        $candidatosIds = $_POST['candidato_id'] ?? [];
        if (!is_array($candidatosIds)) {
            $candidatosIds = [$candidatosIds];
        }
        $candidatosIds = array_filter(array_map('intval', $candidatosIds));
        $observacoes = trim($_POST['observacoes'] ?? '');

        try {
            $resultado = (new RhVagasRepository())->sincronizarCandidatosDaVaga(
                $vagaId,
                $candidatosIds,
                $observacoes !== '' ? $observacoes : null
            );
            $_SESSION['success'] = sprintf(
                'Vínculos atualizados com sucesso! %d adicionado(s), %d removido(s).',
                $resultado['added'],
                $resultado['removed']
            );
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar vínculos de candidatos à vaga.', [
                'vaga_id' => $vagaId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao atualizar vínculos: ' . $e->getMessage();
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas-candidatos/' . $vagaId);
        exit;
    }

    private function loadVagaCandidatosData(int $vagaId): void
    {
        $vagaRepo = new RhVagasRepository();
        $candidatosRepo = new RhCandidatosRepository();

        $vaga = $vagaRepo->getById($vagaId);

        if (!$vaga) {
            $_SESSION['error'] = 'Vaga não encontrada!';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas');
            exit;
        }

        $filters = [
            'nome' => $_GET['nome'] ?? '',
            'email' => $_GET['email'] ?? '',
            'area_interesse' => $_GET['area_interesse'] ?? '',
            'status_processo' => $_GET['status_processo'] ?? '',
            'score_min' => $_GET['score_min'] ?? '',
            'score_max' => $_GET['score_max'] ?? '',
            'classificacao' => $_GET['classificacao'] ?? '',
        ];

        $candidatos = $candidatosRepo->getAll($filters, 1, 10000);
        $allCandidatos = $candidatos['data'] ?? [];

        $candidatosVinculados = $vagaRepo->getCandidatosByVaga($vagaId);
        $candidatosVinculadosIds = array_column($candidatosVinculados, 'rh_candidato_id');

        $pdo = $candidatosRepo->getConnection();
        $stmtAreas = $pdo->query("SELECT DISTINCT area_interesse FROM rh_candidatos WHERE area_interesse IS NOT NULL AND area_interesse != '' ORDER BY area_interesse");
        $areas = $stmtAreas->fetchAll(\PDO::FETCH_COLUMN);

        $this->data['vaga'] = $vaga;
        $this->data['candidatos'] = $allCandidatos;
        $this->data['candidatosVinculadosIds'] = $candidatosVinculadosIds;
        $this->data['filters'] = $filters;
        $this->data['areas'] = $areas;
    }

    private function loadView(): void
    {
        $pageElements = [
            'title_head' => 'Vincular Candidatos à Vaga',
            'menu' => 'rh-vagas',
            'buttonPermission' => ['RhVagasCandidatos'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/candidatos', $this->data);
        $loadView->loadView();
    }
}
