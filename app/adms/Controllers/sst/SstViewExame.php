<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewExame
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-exames');
            exit;
        }
        $repo = new SstExamesRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-exames');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-exame/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_exames', $itemId, $returnUrl);
        
        $this->data['entity'] = array (
  'table' => 'adms_sst_exames',
  'singular' => 'Exame',
  'plural' => 'Exames',
  'prefix' => 'Exame',
  'url' => 'exame',
  'menu' => 'sst-list-exames',
  'icon' => 'fa-stethoscope',
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
    'periodicidade_meses' => 
    array (
      'label' => 'Periodicidade (meses)',
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
    2 => 'periodicidade_meses',
    3 => 'status',
  ),
);
        $pageElements = [
            'title_head' => 'Visualizar Exame - SST',
            'menu' => 'sst-list-exames',
            'buttonPermission' => ['SstViewExame', 'SstUpdateExame', 'SstDeleteExame'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/exames/view', $this->data))->loadView();
    }
}