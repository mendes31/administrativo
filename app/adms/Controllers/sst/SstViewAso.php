<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAsoExamesRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewAso
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }
        $repo = new SstAsosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-aso/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_asos', $itemId, $returnUrl);
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('asos', $itemId);
        $this->data['complementares'] = (new SstAsoExamesRepository())->getByAsoId($itemId);
        $this->data['entity'] = array (
  'table' => 'adms_sst_asos',
  'singular' => 'ASO',
  'plural' => 'ASOs',
  'prefix' => 'Aso',
  'url' => 'aso',
  'menu' => 'sst-list-asos',
  'icon' => 'fa-file-medical',
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
    'adms_sst_exame_id' => 
    array (
      'label' => 'Exame',
      'type' => 'fk_exame',
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
        0 => 'Admissional',
        1 => 'Periódico',
        2 => 'Mudança de função',
        3 => 'Retorno ao trabalho',
        4 => 'Demissional',
      ),
      'required' => true,
    ),
    'data_realizacao' => 
    array (
      'label' => 'Data realização',
      'type' => 'date',
      'required' => true,
    ),
    'data_validade' => 
    array (
      'label' => 'Validade',
      'type' => 'date',
    ),
    'resultado' => 
    array (
      'label' => 'Resultado',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Apto',
        1 => 'Inapto',
        2 => 'Apto com restrição',
      ),
    ),
    'restricoes' => 
    array (
      'label' => 'Restrições',
      'type' => 'textarea',
    ),
    'clinica' => 
    array (
      'label' => 'Clínica',
      'type' => 'text',
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
    3 => 'data_realizacao',
    4 => 'data_validade',
    5 => 'resultado',
  ),
);
        $pageElements = [
            'title_head' => 'Visualizar ASO - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => ['SstViewAso', 'SstUpdateAso', 'SstDeleteAso', 'SstRegistrarResultadosAso'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/asos/view', $this->data))->loadView();
    }
}