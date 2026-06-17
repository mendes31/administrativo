<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewRisco
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }
        $repo = new SstRiscosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-risco/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_riscos', $itemId, $returnUrl);
        
        $this->data['entity'] = array (
  'table' => 'adms_sst_riscos',
  'singular' => 'Risco',
  'plural' => 'Riscos',
  'prefix' => 'Risco',
  'url' => 'risco',
  'menu' => 'sst-list-riscos',
  'icon' => 'fa-exclamation-triangle',
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
    'tipo' => 
    array (
      'label' => 'Tipo',
      'type' => 'text',
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
    2 => 'tipo',
    3 => 'status',
  ),
);
        $pageElements = [
            'title_head' => 'Visualizar Risco - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => ['SstViewRisco', 'SstUpdateRisco', 'SstDeleteRisco'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos/view', $this->data))->loadView();
    }
}