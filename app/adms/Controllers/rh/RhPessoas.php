<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\RhIdentidadeRepository;
use App\adms\Views\Services\LoadViewService;

final class RhPessoas
{
    private array $data = [];

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = ['q' => $_GET['q'] ?? ''];

        $repo = new RhIdentidadeRepository();
        $result = $repo->listPessoas($filters, $page, 20);

        $this->data = [
            'title_head' => 'Pessoas',
            'menu' => 'rh-pessoas',
            'buttonPermission' => ['RhPessoas', 'RhPessoasView'],
            'pessoas' => $result['data'],
            'filters' => $filters,
            'pagination' => PaginationService::generatePagination(
                $result['total'],
                20,
                $page,
                'rh-pessoas',
                array_filter($filters, static fn ($v) => $v !== '' && $v !== null)
            ),
        ];
        $this->data['paginator'] = $this->data['pagination']['html'] ?? '';

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/pessoas/list', $this->data))->loadView();
    }
}
