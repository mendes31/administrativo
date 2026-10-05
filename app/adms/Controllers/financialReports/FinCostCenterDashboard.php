<?php

declare(strict_types=1);

namespace App\adms\Controllers\financialReports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\FinCostCenterSapService;
use App\adms\Views\Services\LoadViewService;

/**
 * Shell dos dashboards de centros de custo SAP.
 * Os dados vêm de FinCostCenterDashboardData (JSON) para a tela abrir na hora.
 */
class FinCostCenterDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Indicadores de Centros de Custo SAP',
            'menu' => 'fin-cost-center-dashboard',
            'buttonPermission' => ['FinCostCenterDashboard', 'FinCostCenterDashboardData'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = $pageLayoutService->configurePageElements($pageElements);

        $base = $_ENV['URL_ADM'] ?? '';
        $this->data['api_url'] = $base . 'fin-cost-center-dashboard-data';
        $this->data['self_url'] = $base . 'fin-cost-center-dashboard';
        $this->data['periodos'] = FinCostCenterSapService::PERIODOS;
        $this->data['periodo_chave'] = (string) ($_GET['periodo'] ?? '3');
        if (!isset(FinCostCenterSapService::PERIODOS[$this->data['periodo_chave']])) {
            $this->data['periodo_chave'] = '3';
        }
        $this->data['data_inicio'] = (string) ($_GET['data_inicio'] ?? '');
        $this->data['data_fim'] = (string) ($_GET['data_fim'] ?? '');
        $this->data['unidade'] = trim((string) ($_GET['unidade'] ?? ''));
        $this->data['centro'] = trim((string) ($_GET['centro'] ?? ''));
        $visao = (string) ($_GET['visao'] ?? 'dashboard');
        if (!in_array($visao, ['dashboard', 'meses', 'centros', 'equipes', 'hierarquia', 'lancamentos'], true)) {
            $visao = 'dashboard';
        }
        $this->data['visao'] = $visao;

        $loadView = new LoadViewService('adms/Views/financialReports/costCenterDashboard', $this->data);
        $loadView->loadView();
    }
}
