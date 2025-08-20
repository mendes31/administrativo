<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdCategoriasTitulares
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'titular' => $_GET['titular'] ?? '',
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

        $repo = new LgpdCategoriasTitularesRepository();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountCategoriasTitulares($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-categorias-titulares',
            array_filter([
                'titular' => $filters['titular'],
                'status' => $filters['status'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Categorias de Titulares LGPD',
            'menu' => 'lgpd-categorias-titulares',
            'buttonPermission' => ['ListLgpdCategoriasTitulares', 'CreateLgpdCategoriasTitulares', 'EditLgpdCategoriasTitulares', 'ViewLgpdCategoriasTitulares', 'DeleteLgpdCategoriasTitulares'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/categorias-titulares/list", $this->data);
        $loadView->loadView();
    }
} 