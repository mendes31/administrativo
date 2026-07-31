<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Views\Services\LoadViewService;

final class TiSistemas
{
    private array $data = [];
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filterNome = trim((string) ($_GET['nome'] ?? ''));
        $filterStatus = trim((string) ($_GET['status'] ?? ''));

        $repo = new TiSistemaRepository();
        $total = $repo->countAll($filterNome, $filterStatus);
        $this->data['sistemas'] = $repo->getAll((int) $page, $this->limitResult, $filterNome, $filterStatus);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'ti-sistemas',
            ['per_page' => $this->limitResult, 'nome' => $filterNome, 'status' => $filterStatus]
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_nome'] = $filterNome;
        $this->data['filter_status'] = $filterStatus;
        $this->data['tipos'] = TiSistemaRepository::TIPOS;

        $pageElements = [
            'title_head' => 'Sistemas (TI)',
            'menu' => 'ti-sistemas',
            'buttonPermission' => ['TiSistemasCreate', 'TiSistemasView', 'TiSistemasUpdate', 'TiAcessosCreate'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/sistemas/list', $this->data))->loadView();
    }
}
