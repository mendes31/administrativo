<?php
namespace App\adms\Controllers\financialReports;

use App\adms\Models\Repository\CostCenterSummaryRepository;
use App\adms\Views\Services\LoadViewService;

class CostCenterSummary
{
    private array|string|null $data = null;

    public function index(): void
    {
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: date('Y');
        $export = isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf']) ? $_GET['export'] : null;
        $repo = new CostCenterSummaryRepository();
        $summary = $repo->getSummaryByYear($year);
        $allCostCenters = $repo->getAllCostCenters();

        // Inicializa todos os centros de custo com meses zerados
        $costCenters = [];
        foreach ($allCostCenters as $cc) {
            $costCenters[$cc['id']] = [
                'name' => $cc['name'],
                'months' => array_fill(1, 12, 0.0),
                'total' => 0.0
            ];
        }
        // Preenche com os valores reais dos movimentos
        foreach ($summary as $row) {
            $ccId = $row['cost_center_id'];
            $month = (int)$row['month'];
            $total = (float)$row['total'];
            if (isset($costCenters[$ccId])) {
                $costCenters[$ccId]['months'][$month] = $total;
                $costCenters[$ccId]['total'] += $total;
            }
        }
        $costCenters = array_values($costCenters);
        usort($costCenters, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        // Exportação
        if ($export === 'excel') {
            $repo->exportToExcel($year, $costCenters);
        } elseif ($export === 'pdf') {
            $repo->exportToPDF($year, $costCenters);
        }

        // Configuração do layout/menu/navbar
        $pageElements = [
            'title_head' => 'Resumo por Centro de Custo',
            'menu' => 'cost-center-summary',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new \App\adms\Controllers\Services\PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $this->data['costCenters'] = $costCenters;
        $this->data['year'] = $year;
        $loadView = new LoadViewService('adms/Views/financialReports/costCenterSummary', $this->data);
        $loadView->loadView();
    }
} 