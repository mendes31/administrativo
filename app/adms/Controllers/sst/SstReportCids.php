<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\SstCidCapituloHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstCidReportService;
use App\adms\Views\Services\LoadViewService;

class SstReportCids
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'data_inicio_de' => $_GET['data_inicio_de'] ?? '',
            'data_inicio_ate' => $_GET['data_inicio_ate'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'natureza' => $_GET['natureza'] ?? '',
            'capitulo_num' => $_GET['capitulo_num'] ?? '',
        ];
        $service = new SstCidReportService();
        $this->data['filters'] = $filters;
        $this->data['report'] = $service->getRelatorio($filters);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['capitulos'] = SstCidCapituloHelper::all();

        $pageElements = [
            'title_head' => 'Relatório por CID - SST',
            'menu' => 'sst-report-cids',
            'buttonPermission' => ['SstReportCids'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/report_cids', $this->data))->loadView();
    }
}
