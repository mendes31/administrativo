<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstRiscoCargoRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateRiscoCargo
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $this->data['item'] = [];
        $this->loadFormData();
        $riscoId = SstRiscoNavigationHelper::riscoIdFromRequest();
        if ($riscoId > 0) {
            $this->data['item']['adms_sst_risco_id'] = $riscoId;
            $this->data['return_risco_id'] = $riscoId;
        }
        $this->data['entity'] = array (
  'table' => 'adms_sst_riscos_cargo',
  'singular' => 'Risco por Cargo',
  'plural' => 'Riscos por Cargo',
  'prefix' => 'RiscoCargo',
  'url' => 'risco-cargo',
  'menu' => 'sst-list-riscos',
  'icon' => 'fa-shield-virus',
  'type' => 'rule',
  'no_view' => true,
  'fields' => 
  array (
    'adms_position_id' => 
    array (
      'label' => 'Cargo',
      'type' => 'fk_position',
    ),
    'adms_department_id' => 
    array (
      'label' => 'Departamento',
      'type' => 'fk_department',
    ),
    'adms_sst_risco_id' => 
    array (
      'label' => 'Risco',
      'type' => 'fk_risco',
      'required' => true,
    ),
    'nivel' => 
    array (
      'label' => 'Nível',
      'type' => 'select',
      'options' => 
      array (
        0 => 'Baixo',
        1 => 'Médio',
        2 => 'Alto',
        3 => 'Crítico',
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
    1 => 'cargo_nome',
    2 => 'departamento_nome',
    3 => 'risco_nome',
    4 => 'nivel',
  ),
);
        $pageElements = [
            'title_head' => 'Create Risco por Cargo - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => ['SstCreateRiscoCargo'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/riscos_cargo/form', $this->data))->loadView();
    }

    private function loadFormData(): void
    {
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['exames'] = (new SstExamesRepository())->getAll(1, 500);
        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500);
        $this->data['medicos'] = (new SstMedicosRepository())->getAll(1, 500);
        $this->data['cids'] = (new SstCidsRepository())->getAll(1, 500);
        $this->data['riscos'] = (new SstRiscosRepository())->getAll(1, 500);
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_riscos_cargo_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-cargo');
            exit;
        }
        $data = [];
        $data['adms_position_id'] = $_POST['adms_position_id'] ?? null;
        $data['adms_department_id'] = $_POST['adms_department_id'] ?? null;
        $data['adms_sst_risco_id'] = $_POST['adms_sst_risco_id'] ?? null;
        $data['nivel'] = $_POST['nivel'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstRiscoCargoRepository();
        $newId = $repo->create($data);
        $riscoId = (int) ($data['adms_sst_risco_id'] ?? SstRiscoNavigationHelper::riscoIdFromRequest());
        if ($newId) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'cargos', 'sst-list-risco-cargo');
        }
        $_SESSION['msg'] = 'Erro ao salvar registro.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco-cargo' . ($riscoId > 0 ? '?adms_sst_risco_id=' . $riscoId : ''));
        exit;
    }
}
