<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\FormacaoPrecoRepository;
use App\adms\Views\Services\LoadViewService;

final class FormacaoPreco
{
    private array $data = [];
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $filter = trim((string) ($_GET['q'] ?? ''));
        $repo = new FormacaoPrecoRepository();
        $total = $repo->countAll($filter);
        $this->data['registros'] = $repo->getAll((int) $page, $this->limitResult, $filter);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'formacao-preco',
            ['q' => $filter]
        );
        $this->data['filter_q'] = $filter;

        $pageElements = [
            'title_head' => 'Formação de Preço',
            'menu' => 'formacao-preco',
            'buttonPermission' => [
                'FormacaoPrecoCreate',
                'FormacaoPrecoView',
                'FormacaoPrecoUpdate',
                'FormacaoPrecoDelete',
                'FormacaoPrecoPdf',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/pricing/list', $this->data))->loadView();
    }
}
