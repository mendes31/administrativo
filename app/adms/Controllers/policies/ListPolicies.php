<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Views\Services\LoadViewService;

class ListPolicies
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $page = max(1, (int) $page);

        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filters = [
            'categoria_id' => $_GET['categoria_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'ativo' => $_GET['ativo'] ?? '',
            'urgente' => $_GET['urgente'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'busca' => $_GET['busca'] ?? '',
        ];

        $repo = new PoliciesRepository();

        // Permissões para decidir escopo de listagem (editores de políticas internas)
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['CreatePolicy', 'UpdatePolicy']);
        $isEditor = is_array($perms) && count($perms) > 0;

        if (!$isEditor) {
            // Usuário comum: somente políticas ativas e dentro da janela de publicação
            $filters['ativo'] = '1';
            $filters['apenas_janela_publicacao'] = true;
        }

        $this->data['policies'] = $repo->getAllPolicies((int) $page, (int) $this->limitResult, $filters);
        $total = $repo->getTotalPolicies($filters);

        $pagination = PaginationService::generatePagination(
            (int) $total,
            (int) $this->limitResult,
            (int) $page,
            'list-policies',
            array_merge($filters, ['per_page' => $this->limitResult])
        );

        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['categorias'] = $repo->getCategorias();
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['filters'] = $filters;
        $this->data['isEditor'] = $isEditor;

        $pageElements = [
            'title_head' => 'Políticas Internas',
            'menu' => 'gestao_pessoas',
            'buttonPermission' => ['ListPolicies', 'CreatePolicy', 'ViewPolicy', 'UpdatePolicy', 'DeletePolicy'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/list', $this->data);
        $loadView->loadView();
    }
}