<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\ScreenResolutionHelper;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListCids
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);

        if (isset($_GET['limpar'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cids');
            exit;
        }

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'capitulo_num' => $_GET['capitulo_num'] ?? '',
            'frequente' => $_GET['frequente'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $allowedPerPage = [10, 20, 50, 100];
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $allowedPerPage, true)) {
            $this->limitResult = (int) $_GET['per_page'];
        } else {
            $this->limitResult = (int) ($paginationSettings['per_page'] ?? 10);
        }
        $repo = new SstCidsRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-cids',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        $this->data['capitulos'] = \App\adms\Helpers\SstCidCapituloHelper::all();
        $this->data['total_cids'] = $total;
        $this->data['entity'] = array (
  'table' => 'adms_sst_cids',
  'singular' => 'CID',
  'plural' => 'CIDs',
  'prefix' => 'Cid',
  'url' => 'cid',
  'menu' => 'sst-list-cids',
  'icon' => 'fa-notes-medical',
  'type' => 'catalog',
  'no_view' => true,
  'fields' => 
  array (
    'codigo' => 
    array (
      'label' => 'Código',
      'type' => 'text',
      'required' => true,
    ),
    'descricao' => 
    array (
      'label' => 'Descrição',
      'type' => 'text',
      'required' => true,
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
    1 => 'codigo',
    2 => 'descricao',
    3 => 'status',
  ),
);
        if ('catalog' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'CIDs - SST',
            'menu' => 'sst-list-cids',
            'buttonPermission' => ['SstCreateCid', 'SstUpdateCid', 'SstDeleteCid', 'SstReportCids'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;
        $this->data['screenResolution'] = $resolution;
        (new LoadViewService('adms/Views/sst/cids/list', $this->data))->loadView();
    }
}