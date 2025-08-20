<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdClassificacoesDadosRepository;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdClassificacoesDados
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'classificacao' => $_GET['classificacao'] ?? '',
            'base_legal_id' => $_GET['base_legal_id'] ?? '',
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

        $repo = new LgpdClassificacoesDadosRepository();
        $basesLegaisRepo = new LgpdBasesLegaisRepository();
        
        $this->data['bases_legais'] = $basesLegaisRepo->getActiveBasesLegais();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountClassificacoesDados($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-classificacoes-dados',
            array_filter([
                'classificacao' => $filters['classificacao'],
                'base_legal_id' => $filters['base_legal_id'],
                'status' => $filters['status'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Classificações de Dados LGPD',
            'menu' => 'lgpd-classificacoes-dados',
            'buttonPermission' => ['ListLgpdClassificacoesDados', 'CreateLgpdClassificacoesDados', 'EditLgpdClassificacoesDados', 'ViewLgpdClassificacoesDados', 'DeleteLgpdClassificacoesDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/classificacoes-dados/list", $this->data);
        $loadView->loadView();
    }
} 