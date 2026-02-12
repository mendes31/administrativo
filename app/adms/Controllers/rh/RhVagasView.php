<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class RhVagasView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Vaga não encontrada', ['id' => (int)$id]);
            $_SESSION['error'] = "Vaga não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-vagas");
            return;
        }

        $repo = new RhVagasRepository();
        $vaga = $repo->getById((int)$id);

        if (!$vaga) {
            GenerateLog::generateLog('error', 'Vaga não encontrada', ['id' => (int)$id]);
            $_SESSION['error'] = "Vaga não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-vagas");
            return;
        }

        $this->data['vaga'] = $vaga;
        $this->data['candidatos'] = $repo->getCandidatosByVaga((int)$id);
        
        // Buscar todos os candidatos disponíveis para vincular (exceto os já vinculados)
        $candidatosVinculadosIds = array_column($this->data['candidatos'], 'rh_candidato_id');
        $candRepo = new \App\adms\Models\Repository\RhCandidatosRepository();
        $todosCandidatos = $candRepo->getAll([], 1, 1000);
        $this->data['candidatos_disponiveis'] = array_filter($todosCandidatos['data'] ?? [], function($c) use ($candidatosVinculadosIds) {
            return !in_array($c['id'], $candidatosVinculadosIds, true);
        });

        $returnUrl = $_ENV['URL_ADM'] . 'rh-vagas-view/' . (int)$id;
        $this->data['log_resumo'] = LogResumoService::getResumo('rh_vagas', (int)$id, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Vaga',
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagas', 'RhVagasEdit', 'RhVagasDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/view', $this->data);
        $loadView->loadView();
    }
}

