<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\SstLegacyEpiEntregaGuard;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SstViewEpiEntrega
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        SstLegacyEpiEntregaGuard::denyAndRedirect();
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-entregas');
            exit;
        }
        $repo = new SstEpiEntregasRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-entregas');
            exit;
        }
        $itemId = (int) $this->data['item']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sst-view-epi-entrega/' . $itemId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_sst_epi_entregas', $itemId, $returnUrl);
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('epi_entregas', $itemId);
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
        $pageElements = [
            'title_head' => 'Visualizar Entrega de EPI - SST',
            'menu' => 'sst-list-epi-entregas',
            'buttonPermission' => ['SstViewEpiEntrega', 'SstUpdateEpiEntrega', 'SstDeleteEpiEntrega'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_entregas/view', $this->data))->loadView();
    }
}