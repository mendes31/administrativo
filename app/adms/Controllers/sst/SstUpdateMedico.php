<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateMedico
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            $_SESSION['msg'] = 'ID não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-medicos');
            exit;
        }
        $repo = new SstMedicosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-medicos');
            exit;
        }
        $this->loadFormData();
        $this->data['entity'] = array (
  'table' => 'adms_sst_medicos',
  'singular' => 'Médico',
  'plural' => 'Médicos',
  'prefix' => 'Medico',
  'url' => 'medico',
  'menu' => 'sst-list-medicos',
  'icon' => 'fa-user-md',
  'type' => 'catalog',
  'no_view' => true,
  'fields' => 
  array (
    'nome' => 
    array (
      'label' => 'Nome',
      'type' => 'text',
      'required' => true,
    ),
    'crm' => 
    array (
      'label' => 'CRM',
      'type' => 'text',
    ),
    'crm_uf' => 
    array (
      'label' => 'UF CRM',
      'type' => 'text',
    ),
    'clinica' => 
    array (
      'label' => 'Clínica',
      'type' => 'text',
    ),
    'telefone' => 
    array (
      'label' => 'Telefone',
      'type' => 'text',
    ),
    'email' => 
    array (
      'label' => 'E-mail',
      'type' => 'email',
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
    2 => 'crm',
    3 => 'clinica',
    4 => 'status',
  ),
);
        $pageElements = [
            'title_head' => 'Update Médico - SST',
            'menu' => 'sst-list-medicos',
            'buttonPermission' => ['SstUpdateMedico'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/medicos/form', $this->data))->loadView();
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

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_medicos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-medicos');
            exit;
        }
        $data = [];
        $data['nome'] = $_POST['nome'] ?? null;
        $data['crm'] = $_POST['crm'] ?? null;
        $data['crm_uf'] = $_POST['crm_uf'] ?? null;
        $data['clinica'] = $_POST['clinica'] ?? null;
        $data['telefone'] = $_POST['telefone'] ?? null;
        $data['email'] = $_POST['email'] ?? null;
        $data['status'] = $_POST['status'] ?? null;

        $repo = new SstMedicosRepository();
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-medicos');
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-medico/' . $id);
        }
        exit;
    }
}
