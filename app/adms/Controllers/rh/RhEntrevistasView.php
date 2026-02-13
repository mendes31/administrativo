<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Views\Services\LoadViewService;

class RhEntrevistasView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Entrevista não encontrada', ['id' => (int)$id]);
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        $repo = new RhEntrevistasRepository();
        $entrevista = $repo->getById((int)$id);

        if (!$entrevista) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        $this->data['entrevista'] = $entrevista;

        $pageElements = [
            'title_head' => 'Visualizar Entrevista',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => ['RhEntrevistas', 'RhEntrevistasEdit', 'RhEntrevistasDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/view', $this->data);
        $loadView->loadView();
    }
}
