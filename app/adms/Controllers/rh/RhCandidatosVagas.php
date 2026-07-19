<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Models\Services\RhCandidatoPermissionService;

class RhCandidatosVagas
{
    private array|string|null $data = null;

    public function index(int $candidatoId): void
    {
        if (!RhCandidatoPermissionService::canAccessCandidato($candidatoId)) {
            $_SESSION['error'] = 'Acesso não autorizado a este candidato.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveCandidatoVagas($candidatoId);
        }

        $this->loadCandidatoVagasData($candidatoId);
        $this->loadView();
    }

    private function saveCandidatoVagas(int $candidatoId): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_rh_vincular_candidato_vaga', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos-vagas/' . $candidatoId);
            exit;
        }

        if (!RhCandidatoPermissionService::canAccessCandidato($candidatoId)) {
            $_SESSION['error'] = 'Acesso não autorizado a este candidato.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            exit;
        }

        $vagasIds = $_POST['vaga_id'] ?? [];
        if (!is_array($vagasIds)) {
            $vagasIds = [$vagasIds];
        }
        $vagasIds = array_filter(array_map('intval', $vagasIds));
        $observacoes = trim($_POST['observacoes'] ?? '');

        $vagaRepo = new RhVagasRepository();
        $todasVagas = ($vagaRepo->getAll([], 1, 1000))['data'] ?? [];
        $vagasPermitidas = [];
        foreach ($todasVagas as $vaga) {
            $vid = (int) ($vaga['id'] ?? 0);
            if ($vid > 0 && RhPermissionService::canManagePipeline($vaga)) {
                $vagasPermitidas[] = $vid;
            }
        }

        // Também permitir gerenciar vínculos já existentes cujo usuário controla a vaga
        foreach ($vagaRepo->getVagasByCandidato($candidatoId) as $vinculo) {
            $vid = (int) ($vinculo['rh_vaga_id'] ?? 0);
            if ($vid > 0 && RhPermissionService::canManagePipelineByVagaId($vid)) {
                $vagasPermitidas[] = $vid;
            }
        }
        $vagasPermitidas = array_values(array_unique($vagasPermitidas));

        try {
            $resultado = $vagaRepo->sincronizarVagasDoCandidato(
                $candidatoId,
                $vagasIds,
                $vagasPermitidas,
                $observacoes !== '' ? $observacoes : null
            );
            $_SESSION['success'] = sprintf(
                'Vínculos atualizados com sucesso! %d adicionado(s), %d removido(s).',
                $resultado['added'],
                $resultado['removed']
            );
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar vínculos de vagas ao candidato.', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao atualizar vínculos: ' . $e->getMessage();
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

        $filters = [
            'titulo' => $_GET['titulo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'area_id' => $_GET['area_id'] ?? '',
            'cargo_id' => $_GET['cargo_id'] ?? '',
            'tipo_contrato' => $_GET['tipo_contrato'] ?? '',
        ];

        $vagas = $vagaRepo->getAll($filters, 1, 1000);
        $todasVagas = $vagas['data'] ?? [];

        $vagasVinculadas = $vagaRepo->getVagasByCandidato($candidatoId);
        $vagasVinculadasIds = array_column($vagasVinculadas, 'rh_vaga_id');

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
            'menu' => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatosVagas'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/vagas', $this->data);
        $loadView->loadView();
    }
}
