<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstRiscoCargoRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListRiscoCargo
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstRiscoCargoRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-risco-cargo',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_riscos_cargo',
  'singular' => 'Risco por Cargo',
  'plural' => 'Riscos por Cargo',
  'prefix' => 'RiscoCargo',
  'url' => 'risco-cargo',
  'menu' => 'sst-list-risco-cargo',
  'icon' => 'fa-shield-virus',
  'type' => 'rule',
  'no_view' => true,
  'fields' => 
  array (
    'adms_position_id' => 
    array (
      'label' => 'Cargo',
      'type' => 'fk_position',
    ),
    'adms_department_id' => 
    array (
      'label' => 'Departamento',
      'type' => 'fk_department',
    ),
    'adms_sst_risco_id' => 
    array (
      'label' => 'Risco',
      'type' => 'fk_risco',
      'required' => true,
    ),
    'nivel' => 
    array (
      'label' => 'Nível',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Baixo',
        1 => 'Médio',
        2 => 'Alto',
        3 => 'Crítico',
      ),
    ),
    'observacoes' => 
    array (
      'label' => 'Observações',
      'type' => 'textarea',
    ),
  ),
  'list_cols' => 
  array (
    0 => 'id',
    1 => 'cargo_nome',
    2 => 'departamento_nome',
    3 => 'risco_nome',
    4 => 'nivel',
  ),
);
        if ('rule' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'Riscos por Cargo - SST',
            'menu' => 'sst-list-risco-cargo',
            'buttonPermission' => ['SstCreateRiscoCargo', 'SstUpdateRiscoCargo', 'SstDeleteRiscoCargo'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos_cargo/list', $this->data))->loadView();
    }
}