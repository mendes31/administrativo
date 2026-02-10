<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class LgpdTermosView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Termo LGPD não encontrado', ['id' => (int)$id]);
            $_SESSION['error'] = "Termo LGPD não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-termos");
            return;
        }

        $repo = new LgpdTermosRepository();
        $this->data['termo'] = $repo->getById((int)$id);

        if (!$this->data['termo']) {
            GenerateLog::generateLog('error', 'Termo LGPD não encontrado', ['id' => (int)$id]);
            $_SESSION['error'] = "Termo LGPD não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-termos");
            return;
        }

        // Resumo de alterações (para botão de Log de Alterações)
        $returnUrl = $_ENV['URL_ADM'] . 'lgpd-termos-view/' . (int)$id;
        $this->data['log_resumo'] = LogResumoService::getResumo('lgpd_termos', (int)$id, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Termo LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermosView', 'LgpdTermosEdit', 'LgpdTermosDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/view', $this->data);
        $loadView->loadView();
    }
}


