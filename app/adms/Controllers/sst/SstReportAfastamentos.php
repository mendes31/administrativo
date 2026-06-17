<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Views\Services\LoadViewService;

class SstReportAfastamentos
{
    private array $data = [];

    public function index(): void
    {
        $service = new SstDashboardService();
        $filters = [
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio_de' => $_GET['data_inicio_de'] ?? '',
            'data_inicio_ate' => $_GET['data_inicio_ate'] ?? '',
        ];
        $this->data['filters'] = $filters;
        $this->data['items'] = $service->getReportAfastamentos($filters);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Relatório de Afastamentos - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => ['SstReportAfastamentos'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/report_afastamentos', $this->data))->loadView();
    }
}
