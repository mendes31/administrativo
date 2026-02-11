<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class RhCandidatosView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Candidato não encontrado', ['id' => (int)$id]);
            $_SESSION['error'] = "Candidato não encontrado!";
            header("Location: {$_ENV['URL_ADM']}rh-candidatos");
            return;
        }

        $repo = new RhCandidatosRepository();
        $candidato = $repo->getById((int)$id);

        if (!$candidato) {
            GenerateLog::generateLog('error', 'Candidato não encontrado', ['id' => (int)$id]);
            $_SESSION['error'] = "Candidato não encontrado!";
            header("Location: {$_ENV['URL_ADM']}rh-candidatos");
            return;
        }

        $this->data['candidato'] = $candidato;
        $this->data['anexos']    = $repo->getAnexosByCandidato((int)$id);

        $returnUrl = $_ENV['URL_ADM'] . 'rh-candidatos-view/' . (int)$id;
        $this->data['log_resumo'] = LogResumoService::getResumo('rh_candidatos', (int)$id, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Candidato',
            'menu'       => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatos', 'RhCandidatosEdit', 'RhCandidatosDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/view', $this->data);
        $loadView->loadView();
    }
}


