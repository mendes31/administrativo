<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;

class RhVagasPipeline
{
    private array|string|null $data = null;

    public function index(int $vagaId): void
    {
        if ($vagaId <= 0) {
            $_SESSION['error'] = 'Vaga não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas');
            return;
        }

        $repo = new RhVagasRepository();
        $vaga = $repo->getById($vagaId);

        if (!$vaga) {
            $_SESSION['error'] = 'Vaga não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas');
            return;
        }

        $this->data['vaga'] = $vaga;

        // Carregar candidatos vinculados para o board Kanban
        $candidatos = $repo->getCandidatosByVaga($vagaId);
        $this->data['candidatos'] = $candidatos;

        // Permissão para gerenciar pipeline (drag & drop)
        $this->data['can_manage_pipeline'] = RhPermissionService::canManagePipeline($vaga);

        $pageElements = [
            'title_head' => 'Pipeline da Vaga (Kanban)',
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagas', 'RhVagasView', 'RhVagasEdit'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/pipeline', $this->data);
        $loadView->loadView();
    }
}

