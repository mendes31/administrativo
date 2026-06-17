<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListAcidentes
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
        $repo = new SstAcidentesRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-acidentes',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_acidentes',
  'singular' => 'Acidente/Incidente',
  'plural' => 'Acidentes e Incidentes',
  'prefix' => 'Acidente',
  'url' => 'acidente',
  'menu' => 'sst-list-acidentes',
  'icon' => 'fa-ambulance',
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
    'tipo' => 
    array (
      'label' => 'Tipo',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Acidente',
        1 => 'Incidente',
        2 => 'Quase acidente',
      ),
    ),
    'data_ocorrencia' => 
    array (
      'label' => 'Data/hora',
      'type' => 'datetime',
      'required' => true,
    ),
    'local' => 
    array (
      'label' => 'Local',
      'type' => 'text',
    ),
    'descricao' => 
    array (
      'label' => 'Descrição',
      'type' => 'textarea',
      'required' => true,
    ),
    'cat_numero' => 
    array (
      'label' => 'Nº CAT',
      'type' => 'text',
    ),
    'cat_data' => 
    array (
      'label' => 'Data CAT',
      'type' => 'date',
    ),
    'investigacao' => 
    array (
      'label' => 'Investigação',
      'type' => 'textarea',
    ),
    'plano_acao' => 
    array (
      'label' => 'Plano de ação',
      'type' => 'textarea',
    ),
    'status' => 
    array (
      'label' => 'Status',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Aberto',
        1 => 'Em investigação',
        2 => 'Encerrado',
      ),
    ),
  ),
  'list_cols' => 
  array (
    0 => 'id',
    1 => 'colaborador_nome',
    2 => 'tipo',
    3 => 'data_ocorrencia',
    4 => 'status',
  ),
);
        if ('employee' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'Acidentes e Incidentes - SST',
            'menu' => 'sst-list-acidentes',
            'buttonPermission' => ['SstViewAcidente', 'SstCreateAcidente', 'SstUpdateAcidente', 'SstDeleteAcidente'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/acidentes/list', $this->data))->loadView();
    }
}