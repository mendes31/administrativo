<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpiEstoqueMinTamanhoRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Repository\SstRiscoEpiRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\SstEpiEstoqueService;
use App\adms\Views\Services\LoadViewService;

class SstViewEpi
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $repo = new SstEpisRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-epi/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_epis', $itemId, $returnUrl);
        $this->data['movimentos'] = (new SstEpiMovimentosRepository())->getByEpiId($itemId, 20);
        $this->data['saldos_tamanho'] = !empty($this->data['item']['controla_tamanho'])
            ? (new SstEpiEstoqueService())->anotarPosicao([$this->data['item']])[0]['saldos_tamanho'] ?? []
            : [];
        $this->data['minimos_tamanho'] = (new SstEpiEstoqueMinTamanhoRepository())->getMapByEpiId($itemId);
        $this->data['riscosRelacionados'] = (new SstRiscoEpiRepository())->getRiscosByEpiId($itemId);
        
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
        $pageElements = [
            'title_head' => 'Visualizar EPI - SST',
            'menu' => 'sst-list-epis',
            'buttonPermission' => ['SstViewEpi', 'SstUpdateEpi', 'SstDeleteEpi', 'SstCreateEpiMovimento', 'SstListEpiMovimentos'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epis/view', $this->data))->loadView();
    }
}