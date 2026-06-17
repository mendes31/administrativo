<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEpiNecessidadeRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEpiNecessidade
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
        $repo = new SstEpiNecessidadeRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-epi-necessidade',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_epi_necessidade',
  'singular' => 'Necessidade de EPI',
  'plural' => 'Necessidades de EPI',
  'prefix' => 'EpiNecessidade',
  'url' => 'epi-necessidade',
  'menu' => 'sst-list-epi-necessidade',
  'icon' => 'fa-list-check',
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
    ),
    'adms_sst_epi_id' => 
    array (
      'label' => 'EPI',
      'type' => 'fk_epi',
      'required' => true,
    ),
    'obrigatorio' => 
    array (
      'label' => 'Obrigatório',
      'type' => 'checkbox',
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
    3 => 'epi_nome',
    4 => 'obrigatorio',
  ),
);
        if ('rule' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'Necessidades de EPI - SST',
            'menu' => 'sst-list-epi-necessidade',
            'buttonPermission' => ['SstCreateEpiNecessidade', 'SstUpdateEpiNecessidade', 'SstDeleteEpiNecessidade'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_necessidade/list', $this->data))->loadView();
    }
}