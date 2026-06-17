<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstPlanosAcaoRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewAcidente
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }
        $repo = new SstAcidentesRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-acidente/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_acidentes', $itemId, $returnUrl);
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('acidentes', $itemId);
        $planosRepo = new SstPlanosAcaoRepository();
        $this->data['planos_acao'] = $planosRepo->getByAcidenteId($itemId);
        $this->data['planos_pendentes'] = $planosRepo->countPendentesByAcidente($itemId);
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
        $pageElements = [
            'title_head' => 'Visualizar Acidente/Incidente - SST',
            'menu' => 'sst-list-acidentes',
            'buttonPermission' => [
                'SstViewAcidente', 'SstUpdateAcidente', 'SstDeleteAcidente',
                'SstCreatePlanoAcao', 'SstUpdatePlanoAcao', 'SstDeletePlanoAcao',
                'SstEmployeeProfile', 'SstGenerateEsocialEvento',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/acidentes/view', $this->data))->loadView();
    }
}