<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstExameNecessidadeRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateExameNecessidade
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstExameNecessidadeRepository();
        $this->loadFormData();
        $this->data['entity'] = array (
  'table' => 'adms_sst_exame_necessidade',
  'singular' => 'Necessidade de Exame',
  'plural' => 'Necessidades de Exame',
  'prefix' => 'ExameNecessidade',
  'url' => 'exame-necessidade',
  'menu' => 'sst-list-exame-necessidade',
  'icon' => 'fa-clipboard-list',
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
    ),
    'adms_sst_exame_id' => 
    array (
      'label' => 'Exame',
      'type' => 'fk_exame',
      'required' => true,
    ),
    'periodicidade_meses' => 
    array (
      'label' => 'Periodicidade (meses)',
      'type' => 'number',
    ),
    'obrigatorio' => 
    array (
      'label' => 'Obrigatório',
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
    1 => 'cargo_nome',
    2 => 'departamento_nome',
    3 => 'exame_nome',
    4 => 'periodicidade_meses',
  ),
);
        $pageElements = [
            'title_head' => 'Create Necessidade de Exame - SST',
            'menu' => 'sst-list-exame-necessidade',
            'buttonPermission' => ['SstCreateExameNecessidade'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/exame_necessidade/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_exame_necessidade_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-exame-necessidade');
            exit;
        }
        $data = [];
        $data['adms_position_id'] = $_POST['adms_position_id'] ?? null;
        $data['adms_department_id'] = $_POST['adms_department_id'] ?? null;
        $data['adms_sst_risco_id'] = $_POST['adms_sst_risco_id'] ?? null;
        $data['adms_sst_exame_id'] = $_POST['adms_sst_exame_id'] ?? null;
        $data['categoria_aso'] = $_POST['categoria_aso'] ?? null;
        $data['periodicidade_meses'] = $_POST['periodicidade_meses'] ?? null;
        $data['obrigatorio'] = isset($_POST['obrigatorio']);
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstExameNecessidadeRepository();
        $newId = $repo->create($data);
        if ($newId) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-exame-necessidade');
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-exame-necessidade');
        }
        exit;
    }
}
