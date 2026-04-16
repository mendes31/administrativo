<?php

namespace App\adms\Controllers\reports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;
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
        $sapOnly = true;

        // Garante que o menu \"Relatórios SAP (API)\" fique em destaque
        $_SESSION['menu_override'] = 'ListDynamicReportsSap';

        // Recupera todos os relatórios do usuário
        $this->data['reports'] = $repo->getUserReports($userId, UserAccessHelper::hasFullSystemAccess());

        // Marca quais relatórios são SAP
        foreach ($this->data['reports'] as &$report) {
            $report['is_sap'] = $this->isSapReport($report);
        }
        unset($report);

        // Apenas relatórios SAP nesta página
        $this->data['reports'] = array_values(array_filter(
            $this->data['reports'],
            fn($report) => !empty($report['is_sap'])
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
            // Mantém submenu \"Relatórios SAP (API)\" em destaque (usa o nome da controller/permission)
            'menu' => 'ListDynamicReportsSap',
            'buttonPermission' => ['DynamicReportBuilder']
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/reports/list', $this->data);
        $loadView->loadView();
    }

    private function isSapReport(array $report): bool
    {
        if (!empty($report['data_source']) && $report['data_source'] === 'sap_b1') {
            return true;
        }

        if (!empty($report['custom_sql'])) {
            return DynamicQueryBuilderService::hasSapSignature($report['custom_sql']);
        }

        return false;
    }
}


