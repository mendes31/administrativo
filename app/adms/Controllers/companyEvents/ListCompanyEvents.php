<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Views\Services\LoadViewService;

class ListCompanyEvents
{
    private array $data = [];

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        $page = max(1, (int)$page);
        $perPage = 15;

        $repo = new CompanyEventsRepository();
        $this->data['events'] = $repo->listAllForAdmin($page, $perPage);
        $total = $repo->countAll();
        $this->data['pagination'] = PaginationService::generatePagination($total, $perPage, $page, 'list-company-events', []);

        $pageElements = [
            'title_head' => 'Eventos corporativos',
            'menu' => 'list-company-events',
            'buttonPermission' => ['CreateCompanyEvent', 'UpdateCompanyEvent', 'DeleteCompanyEvent', 'CompanyEventReport'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/companyEvents/list', $this->data);
        $loadView->loadView();
    }
}
