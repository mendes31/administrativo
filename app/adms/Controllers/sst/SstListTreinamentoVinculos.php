<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\SstTreinamentoStatusHelper;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstListTreinamentoVinculos
{
    private array $data = [];
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'adms_sst_treinamento_id' => $_GET['adms_sst_treinamento_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstTreinamentoVinculosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-treinamento-vinculos',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['filters'] = $filters;
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $this->data['statusOptions'] = SstTreinamentoStatusHelper::labels();
        $pageElements = [
            'title_head' => 'Status Treinamentos SST',
            'menu' => 'sst-list-treinamento-vinculos',
            'buttonPermission' => ['SstViewTreinamentoVinculo', 'SstApplyTreinamento', 'SstSyncTreinamentoVinculos'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_vinculos/list', $this->data))->loadView();
    }
}
