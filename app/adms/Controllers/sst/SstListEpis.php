<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEpis
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
        if (!empty($_GET['estoque_baixo'])) {
            $filters['estoque_baixo'] = true;
        }
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstEpisRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-epis',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['entity'] = array (
  'table' => 'adms_sst_epis',
  'singular' => 'EPI',
  'plural' => 'EPIs',
  'prefix' => 'Epi',
  'url' => 'epi',
  'menu' => 'sst-list-epis',
  'icon' => 'fa-hard-hat',
  'type' => 'catalog',
  'fields' => 
  array (
    'nome' => 
    array (
      'label' => 'Nome',
      'type' => 'text',
      'required' => true,
    ),
    'descricao' => 
    array (
      'label' => 'Descrição',
      'type' => 'textarea',
    ),
    'ca_numero' => 
    array (
      'label' => 'Nº CA',
      'type' => 'text',
    ),
    'ca_validade' => 
    array (
      'label' => 'Validade CA',
      'type' => 'date',
    ),
    'estoque_atual' => 
    array (
      'label' => 'Estoque atual',
      'type' => 'number',
    ),
    'estoque_minimo' => 
    array (
      'label' => 'Estoque mínimo',
      'type' => 'number',
    ),
    'periodicidade_troca_dias' => 
    array (
      'label' => 'Troca (dias)',
      'type' => 'number',
    ),
    'status' => 
    array (
      'label' => 'Status',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Ativo',
        1 => 'Inativo',
      ),
    ),
  ),
  'list_cols' => 
  array (
    0 => 'id',
    1 => 'nome',
    2 => 'ca_numero',
    3 => 'estoque_atual',
    4 => 'status',
  ),
);
        if ('catalog' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'EPIs - SST',
            'menu' => 'sst-list-epis',
            'buttonPermission' => ['SstViewEpi', 'SstCreateEpi', 'SstUpdateEpi', 'SstDeleteEpi', 'SstListEpiMovimentos', 'SstCreateEpiMovimento'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epis/list', $this->data))->loadView();
    }
}