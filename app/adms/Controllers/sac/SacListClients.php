<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Views\Services\LoadViewService;

class SacListClients
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'segment' => $_GET['segment'] ?? '',
            'type_person' => $_GET['type_person'] ?? '',
        ];

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $repo = new SacClientsRepository();
        $total = $repo->getTotalClients($filters);
        $this->data['clients'] = $repo->getAllClients((int) $page, $this->limitResult, $filters);

        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sac-list-clients',
            array_merge(['per_page' => $this->limitResult], $filters)
        );

        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['statuses'] = ['Ativo', 'Inativo', 'Bloqueado'];
        $this->data['type_persons'] = ['PF' => 'Pessoa Física', 'PJ' => 'Pessoa Jurídica'];

        $pageElements = [
            'title_head' => 'Clientes - SAC',
            'menu' => 'sac-list-clients',
            'buttonPermission' => ['SacCreateClient', 'SacViewClient', 'SacUpdateClient', 'SacDeleteClient'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        (new LoadViewService("adms/Views/sac/clients/list", $this->data))->loadView();
    }
}
