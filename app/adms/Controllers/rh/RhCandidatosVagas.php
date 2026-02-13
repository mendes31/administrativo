<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Services\RhPermissionService;

class RhCandidatosVagas
{
    private array|string|null $data = null;

    public function index(int $candidatoId): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveCandidatoVagas($candidatoId);
        }

        $this->loadCandidatoVagasData($candidatoId);
        $this->loadView();
    }

    private function saveCandidatoVagas(int $candidatoId): void
    {
        // CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_rh_vincular_candidato_vaga', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos-vagas/' . $candidatoId);
            exit;
        }

        $vagasIds = $_POST['vaga_id'] ?? [];
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!is_array($vagasIds)) {
            $vagasIds = [$vagasIds];
        }
        $vagasIds = array_filter(array_map('intval', $vagasIds));

        try {
            $vagaRepo = new RhVagasRepository();
            $sucessos = 0;
            $erros = 0;
            $mensagens = [];

            // Buscar vagas já vinculadas a este candidato
            $vagasVinculadas = $vagaRepo->getVagasByCandidato($candidatoId);
            $vagasVinculadasIds = array_column($vagasVinculadas, 'rh_vaga_id');

            // Remover vínculos que não estão mais selecionados
            foreach ($vagasVinculadasIds as $vagaIdVinculada) {
                if (!in_array($vagaIdVinculada, $vagasIds, true)) {
                    try {
                        // Verificar permissão para gerenciar pipeline desta vaga
                        if (!RhPermissionService::canManagePipelineByVagaId($vagaIdVinculada)) {
                            continue;
                        }
                        $vagaRepo->desvincularCandidato($vagaIdVinculada, $candidatoId);
                    } catch (\Exception $e) {
                        $erros++;
                        $mensagens[] = "Erro ao desvincular da vaga ID {$vagaIdVinculada}: " . $e->getMessage();
                    }
                }
            }

            // Adicionar novos vínculos
            foreach ($vagasIds as $vagaId) {
                if ($vagaId <= 0) {
                    continue;
                }

                // Verificar permissão para gerenciar pipeline desta vaga
                if (!RhPermissionService::canManagePipelineByVagaId($vagaId)) {
                    $mensagens[] = "Você não tem permissão para gerenciar a vaga ID {$vagaId}.";
                    $erros++;
                    continue;
                }

                try {
                    if (!in_array($vagaId, $vagasVinculadasIds, true)) {
                        $ok = $vagaRepo->vincularCandidato($vagaId, $candidatoId, $observacoes ?: null);
                        if ($ok) {
                            $sucessos++;
                        } else {
                            $erros++;
                            $mensagens[] = "Vaga ID {$vagaId} não pôde ser vinculada.";
                        }
                    }
                } catch (\Exception $e) {
                    $erros++;
                    $mensagens[] = "Vaga ID {$vagaId}: " . $e->getMessage();
                }
            }

            if ($sucessos > 0 || $erros === 0) {
                $_SESSION['success'] = "Vínculos atualizados com sucesso! {$sucessos} vaga(s) vinculada(s).";
            } else {
                $_SESSION['error'] = 'Erro ao atualizar vínculos: ' . implode(' ', $mensagens);
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar vínculos de vagas ao candidato.', [
                'candidato_id' => $candidatoId,
                'error'        => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro inesperado ao atualizar vínculos!';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos-vagas/' . $candidatoId);
        exit;
    }

    private function loadCandidatoVagasData(int $candidatoId): void
    {
        $candRepo = new RhCandidatosRepository();
        $vagaRepo = new RhVagasRepository();

        $candidato = $candRepo->getById($candidatoId);
        if (!$candidato) {
            $_SESSION['error'] = 'Candidato não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            exit;
        }

        // Filtros para vagas
        $filters = [
            'titulo'        => $_GET['titulo'] ?? '',
            'status'        => $_GET['status'] ?? '',
            'area_id'       => $_GET['area_id'] ?? '',
            'cargo_id'      => $_GET['cargo_id'] ?? '',
            'tipo_contrato' => $_GET['tipo_contrato'] ?? '',
        ];

        // Buscar vagas (sem paginação para seleção)
        $vagas = $vagaRepo->getAll($filters, 1, 1000);
        $todasVagas = $vagas['data'] ?? [];

        // Vagas já vinculadas a este candidato
        $vagasVinculadas = $vagaRepo->getVagasByCandidato($candidatoId);
        $vagasVinculadasIds = array_column($vagasVinculadas, 'rh_vaga_id');

        // Carregar departamentos e cargos para filtros
        $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
        $posRepo = new \App\adms\Models\Repository\PositionsRepository();

        $this->data['candidato'] = $candidato;
        $this->data['vagas'] = $todasVagas;
        $this->data['vagasVinculadasIds'] = $vagasVinculadasIds;
        $this->data['filters'] = $filters;
        $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        $this->data['positions'] = $posRepo->getAllPositions(1, 1000) ?: [];
    }

    private function loadView(): void
    {
        $pageElements = [
            'title_head' => 'Vincular Vagas ao Candidato',
            'menu'       => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatosVagas'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/vagas', $this->data);
        $loadView->loadView();
    }
}

