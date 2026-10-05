<?php

declare(strict_types=1);

namespace App\adms\Controllers\financialReports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CostCenterSummaryRepository;
use App\adms\Views\Services\LoadViewService;

class CostCenterSummary
{
    private array|string|null $data = null;

    public function index(): void
    {
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
        $export = isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'], true) ? $_GET['export'] : null;
        $showZeros = isset($_GET['show_zeros']) && (string) $_GET['show_zeros'] === '1';

        $repo = new CostCenterSummaryRepository();
        $summary = $repo->getSummaryByYear($year);
        $allCostCenters = $repo->getAllCostCenters();

        $costCenters = [];
        foreach ($allCostCenters as $cc) {
            $costCenters[$cc['id']] = [
                'name' => $cc['name'],
                'months' => array_fill(1, 12, 0.0),
                'total' => 0.0,
            ];
        }
        foreach ($summary as $row) {
            $ccId = $row['cost_center_id'];
            $month = (int) $row['month'];
            $total = (float) $row['total'];
            if ($ccId === null || !isset($costCenters[$ccId])) {
                continue;
            }
            if ($month < 1 || $month > 12) {
                continue;
            }
            $costCenters[$ccId]['months'][$month] = $total;
            $costCenters[$ccId]['total'] += $total;
        }
        $costCenters = array_values($costCenters);
        usort($costCenters, static fn(array $a, array $b): int => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        $active = [];
        $emptyCount = 0;
        $monthTotals = array_fill(1, 12, 0.0);
        $grandTotal = 0.0;
        $maxAbs = 0.0;
        foreach ($costCenters as $cc) {
            $has = abs((float) $cc['total']) >= 0.005;
            if ($has) {
                $active[] = $cc;
            } else {
                $emptyCount++;
            }
            $grandTotal += (float) $cc['total'];
            for ($m = 1; $m <= 12; $m++) {
                $val = (float) $cc['months'][$m];
                $monthTotals[$m] += $val;
                $maxAbs = max($maxAbs, abs($val));
            }
        }

        $visible = $showZeros ? $costCenters : $active;

        if ($export === 'excel') {
            $repo->exportToExcel($year, $visible);
        } elseif ($export === 'pdf') {
            $repo->exportToPDF($year, $visible);
        }

        $pageElements = [
            'title_head' => 'Resumo por Centro de Custo',
            'menu' => 'cost-center-summary',
            'buttonPermission' => ['FinCostCenterDashboard'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $this->data['costCenters'] = $visible;
        $this->data['year'] = $year;
        $this->data['show_zeros'] = $showZeros;
        $this->data['empty_count'] = $emptyCount;
        $this->data['active_count'] = count($active);
        $this->data['month_totals'] = $monthTotals;
        $this->data['grand_total'] = $grandTotal;
        $this->data['max_abs'] = $maxAbs;

        $loadView = new LoadViewService('adms/Views/financialReports/costCenterSummary', $this->data);
        $loadView->loadView();
    }
}
