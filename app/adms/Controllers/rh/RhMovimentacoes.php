<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\RhMovimentacoesRepository;
use App\adms\Views\Services\LoadViewService;

final class RhMovimentacoes
{
    private array $data = [];

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
        ];

        $repo = new RhMovimentacoesRepository();
        $result = $repo->list($filters, $page, 20);

        $this->data = [
            'title_head' => 'Movimentações',
            'menu' => 'rh-movimentacoes',
            'buttonPermission' => ['RhMovimentacoes', 'RhMovimentacoesCreate', 'RhMovimentacoesView'],
            'movimentacoes' => $result['data'],
            'filters' => $filters,
            'tipos' => RhMovimentacoesRepository::TIPOS,
            'pagination' => PaginationService::generatePagination(
                $result['total'],
                20,
                $page,
                'rh-movimentacoes',
                array_filter($filters, static fn ($v) => $v !== '' && $v !== null)
            ),
        ];
        $this->data['paginator'] = $this->data['pagination']['html'] ?? '';

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/movimentacoes/list', $this->data))->loadView();
    }
}
