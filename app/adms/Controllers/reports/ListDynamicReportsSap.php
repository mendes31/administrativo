<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\DynamicReportScopeHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista exclusivamente relatórios SAP (API) em uma página própria.
 */
class ListDynamicReportsSap
{
    private array $data = [];

    public function index(): void
    {
        $repo = new DynamicReportsRepository();
        $userId = $_SESSION['user_id'] ?? 0;

        $this->data['reports'] = $repo->getUserReports($userId, UserAccessHelper::hasFullSystemAccess());

        foreach ($this->data['reports'] as &$report) {
            $report['is_sap'] = DynamicReportScopeHelper::isSapReport($report);
        }
        unset($report);

        $this->data['reports'] = array_values(array_filter(
            $this->data['reports'],
            static fn ($report) => !empty($report['is_sap'])
        ));

        $this->data['categories'] = [];
        foreach ($this->data['reports'] as $report) {
            $category = $report['category'] ?? 'Sem Categoria';
            $this->data['categories'][$category][] = $report;
        }

        $title = 'Relatórios SAP (API)';
        $this->data['page_title'] = $title;
        $this->data['is_sap_scope'] = true;

        $pageElements = [
            'title_head' => $title,
            'menu' => 'ListDynamicReportsSap',
            'menu_override' => 'ListDynamicReportsSap',
            'buttonPermission' => ['DynamicReportBuilder'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/reports/list', $this->data);
        $loadView->loadView();
    }
}
