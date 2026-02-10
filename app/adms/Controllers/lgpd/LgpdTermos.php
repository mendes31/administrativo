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

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $repo = new LgpdTermosRepository();
        $this->data['termos'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->getAmount($filters);

        // enviar filtros para a view
        $this->data['filters'] = $filters;
        $this->data['per_page'] = $perPage;

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-termos',
            array_filter([
                'per_page' => $perPage,
                'search' => $filters['search'] ?? null,
                'tipo' => $filters['tipo'] ?? null,
                'status' => $filters['status'] ?? null,
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Termos LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermos', 'LgpdTermosCreate', 'LgpdTermosEdit', 'LgpdTermosNewVersion', 'LgpdTermosView', 'LgpdTermosDelete'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/list', $this->data);
        $loadView->loadView();
    }
}


