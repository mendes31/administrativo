<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PaginationService;

class RhVagas
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'titulo'        => $_GET['titulo'] ?? '',
            'status'        => $_GET['status'] ?? '',
            'area_id'       => $_GET['area_id'] ?? '',
            'cargo_id'      => $_GET['cargo_id'] ?? '',
            'tipo_contrato' => $_GET['tipo_contrato'] ?? '',
        ];

        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $scope = \App\adms\Models\Services\RhPermissionService::resolveVagasListScope();
        $filters['scope_mode'] = $scope['mode'];
        $filters['scope_user_id'] = $scope['user_id'];

        $repo = new RhVagasRepository();
        $result = $repo->getAll($filters, $page, $perPage);
        $this->data['vagas'] = $result['data'] ?? [];
        $this->data['list_scope'] = $scope;
        $total = $result['total'] ?? 0;

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'rh-vagas',
            array_filter([
                'titulo'        => $filters['titulo'],
                'status'        => $filters['status'],
                'area_id'       => $filters['area_id'],
                'cargo_id'      => $filters['cargo_id'],
                'tipo_contrato' => $filters['tipo_contrato'],
                'per_page'      => $perPage,
            ])
        );
        $this->data['paginator'] = $pagination['html'] ?? '';

        // Carregar departamentos e cargos para filtros
        $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
        $posRepo = new \App\adms\Models\Repository\PositionsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        $this->data['positions'] = $posRepo->getAllPositions(1, 1000) ?: [];

        $pageElements = [
            'title_head' => 'Vagas de Emprego',
            'menu'       => 'rh-vagas',
            'buttonPermission' => ['RhVagas', 'RhVagasCreate', 'RhVagasView', 'RhVagasEdit', 'RhVagasDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/vagas/list', $this->data);
        $loadView->loadView();
    }
}

