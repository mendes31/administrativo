<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAfastamentosRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Views\Services\LoadViewService;

class SstCreateAfastamento
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstAfastamentosRepository();
        $this->loadFormData();
        $this->data['item'] = [];
        if (!empty($_GET['adms_user_id'])) {
            $this->data['item']['adms_user_id'] = (int) $_GET['adms_user_id'];
        }
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
            'title_head' => 'Create Afastamento - SST',
            'menu' => 'sst-list-afastamentos',
            'buttonPermission' => ['SstCreateAfastamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/afastamentos/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_afastamentos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-afastamentos');
            exit;
        }
        $data = [];
        $data['adms_user_id'] = $_POST['adms_user_id'] ?? null;
        $data['adms_sst_cid_id'] = $_POST['adms_sst_cid_id'] ?? null;
        $data['adms_sst_medico_id'] = $_POST['adms_sst_medico_id'] ?? null;
        $data['tipo'] = $_POST['tipo'] ?? null;
        $data['data_inicio'] = $_POST['data_inicio'] ?? null;
        $data['data_fim'] = $_POST['data_fim'] ?? null;
        $data['dias_afastamento'] = $_POST['dias_afastamento'] ?? null;
        $data['data_retorno'] = $_POST['data_retorno'] ?? null;
        $data['status'] = $_POST['status'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstAfastamentosRepository();
        $newId = $repo->create($data);
        if ($newId) {
            (new SstAnexosUploadService())->processUploads('afastamentos', (int) $newId);
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-afastamento/' . $newId);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-afastamento');
        }
        exit;
    }
}
