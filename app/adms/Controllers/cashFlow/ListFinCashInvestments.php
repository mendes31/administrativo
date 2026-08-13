<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\cashFlow\FinCashInvestmentRepository;
use App\adms\Views\Services\LoadViewService;

class ListFinCashInvestments
{
    private array|string|null $data = null;
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filtros = [
            'bank_label' => $_GET['bank_label'] ?? '',
            'movement_type' => $_GET['movement_type'] ?? '',
            'category' => $_GET['category'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $repo = new FinCashInvestmentRepository();
        $this->data['investments'] = $repo->getAll((int) $page, $this->limitResult, $filtros);
        $total = $repo->countAll($filtros);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'list-fin-cash-investments',
            array_merge($filtros, ['per_page' => $this->limitResult])
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_delete_fin_cash_investment');

        $pageElements = [
            'title_head' => 'Aplicações Financeiras',
            'menu' => 'list-fin-cash-investments',
            'buttonPermission' => [
                'CreateFinCashInvestment',
                'UpdateFinCashInvestment',
                'DeleteFinCashInvestment',
                'FinCashFlowDashboard',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/cashFlow/investments', $this->data);
        $loadView->loadView();
    }
}
