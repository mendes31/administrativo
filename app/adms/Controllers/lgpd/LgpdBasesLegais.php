<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdBasesLegais
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'base_legal' => $_GET['base_legal'] ?? '',
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

        $repo = new LgpdBasesLegaisRepository();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountBasesLegais($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-bases-legais',
            array_filter([
                'base_legal' => $filters['base_legal'],
                'status' => $filters['status'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Bases Legais LGPD',
            'menu' => 'lgpd-bases-legais',
            'buttonPermission' => ['ListLgpdBasesLegais', 'CreateLgpdBasesLegais', 'EditLgpdBasesLegais', 'ViewLgpdBasesLegais', 'DeleteLgpdBasesLegais'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/bases-legais/list", $this->data);
        $loadView->loadView();
    }
} 