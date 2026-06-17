<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEpiEntregas
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
        $repo = new SstEpiEntregasRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-epi-entregas',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_epi_entregas',
  'singular' => 'Entrega de EPI',
  'plural' => 'Entregas de EPI',
  'prefix' => 'EpiEntrega',
  'url' => 'epi-entrega',
  'menu' => 'sst-list-epi-entregas',
  'icon' => 'fa-hand-holding',
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
    'adms_sst_epi_id' => 
    array (
      'label' => 'EPI',
      'type' => 'fk_epi',
      'required' => true,
    ),
    'tipo_movimento' => 
    array (
      'label' => 'Movimento',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Entrega',
        1 => 'Devolução',
        2 => 'Substituição',
        3 => 'Perda/Dano',
      ),
    ),
    'quantidade' => 
    array (
      'label' => 'Quantidade',
      'type' => 'number',
    ),
    'data_movimento' => 
    array (
      'label' => 'Data',
      'type' => 'date',
      'required' => true,
    ),
    'data_prevista_troca' => 
    array (
      'label' => 'Prev. troca',
      'type' => 'date',
    ),
    'termo_assinado' => 
    array (
      'label' => 'Termo assinado',
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
    1 => 'colaborador_nome',
    2 => 'epi_nome',
    3 => 'tipo_movimento',
    4 => 'data_movimento',
  ),
);
        if ('employee' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'Entregas de EPI - SST',
            'menu' => 'sst-list-epi-entregas',
            'buttonPermission' => ['SstViewEpiEntrega', 'SstCreateEpiEntrega', 'SstUpdateEpiEntrega', 'SstDeleteEpiEntrega'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_entregas/list', $this->data))->loadView();
    }
}