<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\DynamicReportScopeHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Views\Services\LoadViewService;

class ListDynamicReports
{
    private array $data = [];

    public function index(): void
    {
        $repo = new DynamicReportsRepository();
        $userId = $_SESSION['user_id'] ?? 0;
        $sapOnly = false;

        $this->data['reports'] = $repo->getUserReports($userId, UserAccessHelper::hasFullSystemAccess());

        foreach ($this->data['reports'] as &$report) {
            $report['is_sap'] = DynamicReportScopeHelper::isSapReport($report);
        }
        unset($report);

        $this->data['reports'] = array_values(array_filter(
            $this->data['reports'],
            static function ($report) use ($sapOnly) {
                return $sapOnly
                    ? !empty($report['is_sap'])
                    : empty($report['is_sap']);
            }
        ));

        $this->data['categories'] = [];
        foreach ($this->data['reports'] as $report) {
            $category = $report['category'] ?? 'Sem Categoria';
            $this->data['categories'][$category][] = $report;
        }

        $title = 'Relatórios Locais';
        $this->data['page_title'] = $title;
        $this->data['is_sap_scope'] = false;

        $pageElements = [
            'title_head' => $title,
            'menu' => 'ListDynamicReports',
            'menu_override' => 'ListDynamicReports',
            'buttonPermission' => ['DynamicReportBuilder'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/reports/list', $this->data);
        $loadView->loadView();
    }
}
