<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PaginationService;

class RhCandidatos
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'nome'           => $_GET['nome'] ?? '',
            'email'          => $_GET['email'] ?? '',
            'origem'         => $_GET['origem'] ?? '',
            'status_processo'=> $_GET['status_processo'] ?? '',
        ];

        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $scope = RhPermissionService::resolveCandidatosListScope();
        $filters['scope_mode'] = $scope['mode'];
        $filters['scope_user_id'] = $scope['user_id'];

        $repo = new RhCandidatosRepository();
        $result = $repo->getAll($filters, $page, $perPage);
        $this->data['candidatos'] = $result['data'] ?? [];
        $this->data['list_scope'] = $scope;
        $total = $result['total'] ?? 0;

        // Vagas abertas para vinculação rápida — respeita escopo de vagas.
        $vagasScope = RhPermissionService::resolveVagasListScope();
        $vagasRepo = new RhVagasRepository();
        $vagasAbertas = $vagasRepo->getAll([
            'status' => 'aberta',
            'scope_mode' => $vagasScope['mode'],
            'scope_user_id' => $vagasScope['user_id'],
        ], 1, 1000);
        $this->data['vagas_abertas'] = $vagasAbertas['data'] ?? [];

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'rh-candidatos',
            array_filter([
                'nome'            => $filters['nome'],
                'email'           => $filters['email'],
                'origem'          => $filters['origem'],
                'status_processo' => $filters['status_processo'],
                'per_page'        => $perPage,
            ])
        );
        $this->data['paginator'] = $pagination['html'] ?? '';

        $pageElements = [
            'title_head' => 'Currículos / Candidatos',
            'menu'       => 'rh-candidatos',
            'buttonPermission' => ['RhCandidatos', 'RhCandidatosCreate', 'RhCandidatosView', 'RhCandidatosEdit', 'RhCandidatosDelete'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/list', $this->data);
        $loadView->loadView();
    }
}
