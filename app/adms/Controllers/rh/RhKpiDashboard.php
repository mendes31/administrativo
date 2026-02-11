<?php
declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\GenerateLog;

class RhKpiDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        try {
            $repo = new RhCandidatosRepository();
            $stats = $repo->getDashboardStats();
            $this->data['stats'] = $stats;
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao carregar dashboard de recrutamento.', [
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao carregar o dashboard de recrutamento.';
        }

        $pageElements = [
            'title_head' => 'Dashboard Recrutamento / Currículos',
            'menu'       => 'rh-kpi-dashboard',
            'buttonPermission' => [
                'RhCandidatos',
                'RhCandidatosView',
                'RhCandidatosCreate',
                'RhCandidatosEdit',
                'RhCandidatosDelete',
                'RhKpiDashboard',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/kpiDashboard', $this->data);
        $loadView->loadView();
    }
}


