<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdRopa
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'departamento_id' => $_GET['departamento_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'atividade' => $_GET['atividade'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdRopaRepository();
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountRopa($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-ropa',
            array_filter([
                'departamento_id' => $filters['departamento_id'],
                'status' => $filters['status'],
                'atividade' => $filters['atividade'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Registro de Atividades de Tratamento (ROPA)',
            'menu' => 'lgpd-ropa',
            'buttonPermission' => ['ListLgpdRopa', 'CreateLgpdRopa', 'EditLgpdRopa', 'ViewLgpdRopa', 'DeleteLgpdRopa'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/ropa/list", $this->data);
        $loadView->loadView();
    }
} 