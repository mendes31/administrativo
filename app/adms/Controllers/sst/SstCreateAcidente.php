<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAcidentesRepository;
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

class SstCreateAcidente
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstAcidentesRepository();
        $this->loadFormData();
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
            'title_head' => 'Create Acidente/Incidente - SST',
            'menu' => 'sst-list-acidentes',
            'buttonPermission' => ['SstCreateAcidente'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/acidentes/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_acidentes_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }
        $data = [];
        $data['adms_user_id'] = $_POST['adms_user_id'] ?? null;
        $data['adms_sst_cid_id'] = $_POST['adms_sst_cid_id'] ?? null;
        $data['tipo'] = $_POST['tipo'] ?? null;
        $data['data_ocorrencia'] = $_POST['data_ocorrencia'] ?? null;
        $data['local'] = $_POST['local'] ?? null;
        $data['descricao'] = $_POST['descricao'] ?? null;
        $data['cat_numero'] = $_POST['cat_numero'] ?? null;
        $data['cat_data'] = $_POST['cat_data'] ?? null;
        $data['investigacao'] = $_POST['investigacao'] ?? null;
        $data['plano_acao'] = $_POST['plano_acao'] ?? null;
        $data['status'] = $_POST['status'] ?? null;

        $repo = new SstAcidentesRepository();
        $newId = $repo->create($data);
        if ($newId) {
            (new SstAnexosUploadService())->processUploads('acidentes', (int) $newId);
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-acidente/' . $newId);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-acidente');
        }
        exit;
    }
}
