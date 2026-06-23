<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstListTreinamentos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'nr_referencia' => $_GET['nr_referencia'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstTreinamentosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-treinamentos',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = $this->entityConfig();
        $pageElements = [
            'title_head' => 'Treinamentos SST',
            'menu' => 'sst-list-treinamentos',
            'buttonPermission' => ['SstViewTreinamento', 'SstCreateTreinamento', 'SstUpdateTreinamento', 'SstDeleteTreinamento'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamentos/list', $this->data))->loadView();
    }

    /** @return array<string, mixed> */
    private function entityConfig(): array
    {
        $cfg = require dirname(__DIR__, 4) . '/scripts/sst_entities_config.php';

        return $cfg['treinamentos'];
    }
}
