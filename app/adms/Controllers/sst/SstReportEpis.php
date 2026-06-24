<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Views\Services\LoadViewService;

class SstReportEpis
{
    private array $data = [];

    public function index(): void
    {
        $service = new SstDashboardService();
        $filters = [
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status_vencimento' => $_GET['status_vencimento'] ?? '',
            'termo_assinado' => $_GET['termo_assinado'] ?? '',
        ];
                $this->data['filters'] = $filters;
                $this->data['items'] = $service->getReportEpis($filters);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $pageElements = [
            'title_head' => 'Relatório de EPIs - SST',
            'menu' => 'sst-report-epis',
            'buttonPermission' => ['SstReportEpis'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/report_epis', $this->data))->loadView();
    }
}