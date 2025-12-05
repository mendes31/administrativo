<?php

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para Relatórios de RH
 */
class PeopleReports
{
    private array|string|null $data = null;

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Relatórios de RH',
            'menu' => 'people-reports',
            'buttonPermission' => [
                'PeopleAnalytics',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/analytics/people_reports', $this->data);
        $loadView->loadView();
    }
}

