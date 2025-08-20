<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdFinalidadesRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdFinalidades
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'finalidade' => $_GET['finalidade'] ?? '',
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

        $repo = new LgpdFinalidadesRepository();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountFinalidades($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-finalidades',
            array_filter([
                'finalidade' => $filters['finalidade'],
                'status' => $filters['status'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Finalidades LGPD',
            'menu' => 'lgpd-finalidades',
            'buttonPermission' => ['ListLgpdFinalidades', 'CreateLgpdFinalidades', 'EditLgpdFinalidades', 'ViewLgpdFinalidades', 'DeleteLgpdFinalidades'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/finalidades/list", $this->data);
        $loadView->loadView();
    }
} 