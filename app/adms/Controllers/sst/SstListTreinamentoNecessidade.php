<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstTreinamentoNecessidadeRepository;
use App\adms\Views\Services\LoadViewService;

class SstListTreinamentoNecessidade
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = ['search' => $_GET['search'] ?? ''];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstTreinamentoNecessidadeRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-treinamento-necessidade',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $cfg = require dirname(__DIR__, 4) . '/scripts/sst_entities_config.php';
        $this->data['entity'] = $cfg['treinamento_necessidade'];
        $this->data['filters'] = $filters;
        $pageElements = [
            'title_head' => 'Necessidades de Treinamento SST',
            'menu' => 'sst-list-treinamento-necessidade',
            'buttonPermission' => ['SstCreateTreinamentoNecessidade', 'SstUpdateTreinamentoNecessidade', 'SstDeleteTreinamentoNecessidade'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_necessidade/list', $this->data))->loadView();
    }
}
