<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhPersonnelRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/** Listagem de requisições de pessoal (Talentos). */
class RhPersonnelRequests
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'area_id' => $_GET['area_id'] ?? '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        try {
            $repo = new RhPersonnelRequestsRepository();
            $result = $repo->getAll($filters, $page, $perPage);
            $this->data['requests'] = $result['data'];
            $this->data['total'] = $result['total'];
            $this->data['filters'] = $filters;
            $this->data['page'] = $page;
            $this->data['per_page'] = $perPage;

            $deptRepo = new \App\adms\Models\Repository\DepartmentsRepository();
            $this->data['departments'] = $deptRepo->getAllDepartments(1, 1000) ?: [];
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao listar requisições de pessoal.', [
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao carregar requisições. Verifique se a migration foi aplicada.';
            $this->data['requests'] = [];
            $this->data['total'] = 0;
            $this->data['filters'] = $filters;
            $this->data['page'] = $page;
            $this->data['per_page'] = $perPage;
            $this->data['departments'] = [];
        }

        $pageElements = [
            'title_head' => 'Requisições de Pessoal',
            'menu' => 'rh-personnel-requests',
            'buttonPermission' => ['RhPersonnelRequests', 'RhPersonnelRequestsCreate'],
        ];
        $layout = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $layout->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/rh/personnel_requests/list', $this->data))->loadView();
    }
}
