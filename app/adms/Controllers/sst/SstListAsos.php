<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListAsos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        $onlyUserId = !empty($_GET['adms_user_id']) ? (int) $_GET['adms_user_id'] : null;
        $sync = (new \App\adms\Models\Services\SstAsoSolicitacaoService())->sincronizarSolicitacoesPendentes($onlyUserId);
        if ($sync['criados'] > 0 && empty($_SESSION['msg'])) {
            $_SESSION['msg'] = $sync['criados'] . ' solicitação(ões) de ASO gerada(s) automaticamente a partir dos vínculos.';
            $_SESSION['msg_type'] = 'info';
        }

        $semFiltrosNaUrl = !array_key_exists('search', $_GET)
            && !array_key_exists('adms_user_id', $_GET)
            && !array_key_exists('status', $_GET)
            && !array_key_exists('page', $_GET)
            && !array_key_exists('per_page', $_GET);

        $filters = [
            'search' => $_GET['search'] ?? '',
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'status' => $_GET['status'] ?? ($semFiltrosNaUrl ? 'Aguardando exames' : ''),
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }
        $repo = new SstAsosRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limitResult, $filters);
        $this->data['aguardando_count'] = $repo->countAguardando();
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'sst-list-asos',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
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
        if ('employee' === 'employee') {
            $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        }
        $pageElements = [
            'title_head' => 'ASOs - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => ['SstViewAso', 'SstCreateAso', 'SstUpdateAso', 'SstDeleteAso', 'SstRegistrarResultadosAso'],
        ];
        $this->data = array_merge($this->data ?? [], (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/asos/list', $this->data))->loadView();
    }
}