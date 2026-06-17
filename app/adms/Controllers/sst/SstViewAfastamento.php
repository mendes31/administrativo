<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAfastamentosRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewAfastamento
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-afastamentos');
            exit;
        }
        $repo = new SstAfastamentosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-afastamentos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-afastamento/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_afastamentos', $itemId, $returnUrl);
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('afastamentos', $itemId);
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
        $pageElements = [
            'title_head' => 'Visualizar Afastamento - SST',
            'menu' => 'sst-list-afastamentos',
            'buttonPermission' => ['SstViewAfastamento', 'SstUpdateAfastamento', 'SstDeleteAfastamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/afastamentos/view', $this->data))->loadView();
    }
}