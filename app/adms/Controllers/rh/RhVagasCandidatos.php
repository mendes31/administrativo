<?php

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\GenerateLog;

class RhVagasCandidatos
{
    private array|string|null $data = null;

    public function index(int $vagaId): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveVagaCandidatos($vagaId);
        }

        $this->loadVagaCandidatosData($vagaId);
        $this->loadView();
    }

    private function saveVagaCandidatos(int $vagaId): void
    {
        $candidatosIds = $_POST['candidato_id'] ?? [];
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Garantir que seja array
        if (!is_array($candidatosIds)) {
            $candidatosIds = [$candidatosIds];
        }
        $candidatosIds = array_filter(array_map('intval', $candidatosIds));

        try {
            $vagaRepo = new RhVagasRepository();
            $sucessos = 0;
            $erros = 0;
            $mensagens = [];

            // Buscar candidatos já vinculados
            $candidatosVinculados = $vagaRepo->getCandidatosByVaga($vagaId);
            $candidatosVinculadosIds = array_column($candidatosVinculados, 'rh_candidato_id');

            // Remover vínculos que não estão mais na lista
            foreach ($candidatosVinculadosIds as $candidatoIdVinculado) {
                if (!in_array($candidatoIdVinculado, $candidatosIds, true)) {
                    try {
                        $vagaRepo->desvincularCandidato($vagaId, $candidatoIdVinculado);
                    } catch (\Exception $e) {
                        $erros++;
                        $mensagens[] = "Erro ao desvincular candidato ID {$candidatoIdVinculado}: " . $e->getMessage();
                    }
                }
            }

            // Adicionar novos vínculos
            foreach ($candidatosIds as $candidatoId) {
                if ($candidatoId <= 0) {
                    continue;
                }
                try {
                    // Verificar se já está vinculado
                    if (!in_array($candidatoId, $candidatosVinculadosIds, true)) {
                        $ok = $vagaRepo->vincularCandidato($vagaId, $candidatoId, $observacoes ?: null);
                        if ($ok) {
                            $sucessos++;
                        } else {
                            $erros++;
                            $mensagens[] = "Candidato ID {$candidatoId} não pôde ser vinculado.";
                        }
                    }
                } catch (\Exception $e) {
                    $erros++;
                    $mensagens[] = "Candidato ID {$candidatoId}: " . $e->getMessage();
                }
            }

            if ($sucessos > 0 || $erros === 0) {
                $_SESSION['success'] = "Vínculos atualizados com sucesso! {$sucessos} candidato(s) vinculado(s).";
            } else {
                $_SESSION['error'] = 'Erro ao atualizar vínculos: ' . implode(' ', $mensagens);
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar vínculos de candidatos à vaga.', [
                'vaga_id' => $vagaId,
                'error'   => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro inesperado ao atualizar vínculos!';
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

        // Buscar todos os candidatos com filtros
        $filters = [
            'nome'            => $_GET['nome'] ?? '',
            'email'           => $_GET['email'] ?? '',
            'area_interesse'  => $_GET['area_interesse'] ?? '',
            'status_processo' => $_GET['status_processo'] ?? '',
            'score_min'       => $_GET['score_min'] ?? '',
            'score_max'       => $_GET['score_max'] ?? '',
            'classificacao'   => $_GET['classificacao'] ?? '',
        ];

        // Buscar todos os candidatos (sem paginação para a tabela de seleção)
        $candidatos = $candidatosRepo->getAll($filters, 1, 10000);
        $allCandidatos = $candidatos['data'] ?? [];

        // Buscar candidatos já vinculados à vaga
        $candidatosVinculados = $vagaRepo->getCandidatosByVaga($vagaId);
        $candidatosVinculadosIds = array_column($candidatosVinculados, 'rh_candidato_id');

        // Buscar áreas de interesse únicas para o filtro
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
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagasCandidatos'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/candidatos', $this->data);
        $loadView->loadView();
    }
}

