<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstAfastamentosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListAfastamentos
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
        $repo = new SstAfastamentosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-afastamentos',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_afastamentos',
  'singular' => 'Afastamento',
  'plural' => 'Afastamentos',
  'prefix' => 'Afastamento',
  'url' => 'afastamento',
  'menu' => 'sst-list-afastamentos',
  'icon' => 'fa-procedures',
  'type' => 'employee',
  'has_anexos' => true,
  'fields' => 
  array (
    'adms_user_id' => 
    array (
      'label' => 'Colaborador',
      'type' => 'user',
      'required' => true,
    ),
    'adms_sst_cid_id' => 
    array (
      'label' => 'CID',
      'type' => 'fk_cid',
    ),
    'adms_sst_medico_id' => 
    array (
      'label' => 'Médico',
      'type' => 'fk_medico',
    ),
    'tipo' => 
    array (
      'label' => 'Tipo',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Doença',
        1 => 'Acidente de trabalho',
        2 => 'Licença',
        3 => 'Maternidade',
        4 => 'Outro',
      ),
    ),
    'data_inicio' => 
    array (
      'label' => 'Data início',
      'type' => 'date',
      'required' => true,
    ),
    'data_fim' => 
    array (
      'label' => 'Data fim',
      'type' => 'date',
    ),
    'dias_afastamento' => 
    array (
      'label' => 'Dias',
      'type' => 'number',
    ),
    'data_retorno' => 
    array (
      'label' => 'Data retorno',
      'type' => 'date',
    ),
    'status' => 
    array (
      'label' => 'Status',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Ativo',
        1 => 'Encerrado',
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
    1 => 'colaborador_nome',
    2 => 'tipo',
    3 => 'data_inicio',
    4 => 'data_fim',
    5 => 'status',
  ),
);
        if ('employee' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'Afastamentos - SST',
            'menu' => 'sst-list-afastamentos',
            'buttonPermission' => ['SstViewAfastamento', 'SstCreateAfastamento', 'SstUpdateAfastamento', 'SstDeleteAfastamento'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/afastamentos/list', $this->data))->loadView();
    }
}