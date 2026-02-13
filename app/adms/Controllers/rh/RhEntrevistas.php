<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PaginationService;

class RhEntrevistas
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'rh_candidato_id'   => $_GET['rh_candidato_id'] ?? '',
            'rh_vaga_id'        => $_GET['rh_vaga_id'] ?? '',
            'tipo'              => $_GET['tipo'] ?? '',
            'resultado'         => $_GET['resultado'] ?? '',
            'data_de'           => $_GET['data_de'] ?? '',
            'data_ate'          => $_GET['data_ate'] ?? '',
            'entrevistador_id'  => $_GET['entrevistador_id'] ?? '',
        ];

        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $repo = new RhEntrevistasRepository();
        $result = $repo->getAll(array_filter($filters), $page, $perPage);
        $this->data['entrevistas'] = $result['data'] ?? [];
        $total = $result['total'] ?? 0;

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'rh-entrevistas',
            array_filter(array_merge($filters, ['per_page' => $perPage]))
        );
        $this->data['paginator'] = $pagination['html'] ?? '';

        $userRepo = new \App\adms\Models\Repository\UsersRepository();
        $this->data['users'] = $userRepo->getAllUsersSelect() ?: [];

        $pageElements = [
            'title_head' => 'Entrevistas',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => ['RhEntrevistas', 'RhEntrevistasCreate', 'RhEntrevistasView', 'RhEntrevistasEdit', 'RhEntrevistasDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/list', $this->data);
        $loadView->loadView();
    }
}
