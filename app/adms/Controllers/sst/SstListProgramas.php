<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstProgramasRepository;
use App\adms\Views\Services\LoadViewService;

class SstListProgramas
{
    private array $data = [];
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'vigencia' => $_GET['vigencia'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $repo = new SstProgramasRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-programas',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Programas SST (PGR/PCMSO) - SST',
            'menu' => 'sst-list-programas',
            'buttonPermission' => ['SstListProgramas', 'SstCreatePrograma', 'SstViewPrograma', 'SstUpdatePrograma', 'SstDeletePrograma'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/programas/list', $this->data))->loadView();
    }
}
