<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiposDadosRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdTiposDados
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'tipo_dado' => $_GET['tipo_dado'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // Converter valores numéricos para strings do banco
        if ($filters['status'] === '1') {
            $filters['status'] = 'Ativo';
        } elseif ($filters['status'] === '0') {
            $filters['status'] = 'Inativo';
        }
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdTiposDadosRepository();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountTiposDados($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-tipos-dados',
            array_filter([
                'tipo_dado' => $filters['tipo_dado'],
                'status' => $filters['status'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Tipos de Dados LGPD',
            'menu' => 'lgpd-tipos-dados',
            'buttonPermission' => ['ListLgpdTiposDados', 'CreateLgpdTiposDados', 'EditLgpdTiposDados', 'ViewLgpdTiposDados', 'DeleteLgpdTiposDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/tipos-dados/list", $this->data);
        $loadView->loadView();
    }
} 