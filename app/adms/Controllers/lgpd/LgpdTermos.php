<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PaginationService;

class LgpdTermos
{
    private array|string|null $data = null;

    public function index(): void
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdTermosRepository();
        $this->data['termos'] = $repo->getAll($page, $perPage);
        $total = $repo->getAmount();

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-termos',
            array_filter([
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Termos LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermos', 'LgpdTermosCreate', 'LgpdTermosEdit', 'LgpdTermosView', 'LgpdTermosDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/list', $this->data);
        $loadView->loadView();
    }
}


