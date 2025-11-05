<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard de Vendas SAP B1
 * Exibe KPIs, gráficos e filtros para análise de vendas
 */
class SalesDashboard
{
    private array $data = [];

    public function index(): void
    {
        // Obter anos disponíveis (últimos 5 anos)
        $currentYear = date('Y');
        $years = [];
        for ($i = 0; $i < 5; $i++) {
            $years[] = $currentYear - $i;
        }
        $this->data['years'] = $years;
        
        // Meses
        $this->data['months'] = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        
        // Configurar página
        $pageElements = [
            'title_head' => 'Dashboard de Vendas - SAP B1',
            'menu' => 'SalesDashboard',
            'buttonPermission' => []
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/reports/sales-dashboard', $this->data);
        $loadView->loadView();
    }
}

