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
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Views\Services\LoadViewService;

class SstUpdateAfastamento
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
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-afastamentos');
            exit;
        }
        $repo = new SstAfastamentosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-afastamentos');
            exit;
        }
        $this->loadFormData();
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('afastamentos', (int) $id);
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
            'title_head' => 'Update Afastamento - SST',
            'menu' => 'sst-list-afastamentos',
            'buttonPermission' => ['SstUpdateAfastamento'],
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

    private function update(int $id): void
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
        $data['adms_sst_cid_secundario_id'] = $_POST['adms_sst_cid_secundario_id'] ?? null;
        $data['adms_sst_medico_id'] = $_POST['adms_sst_medico_id'] ?? null;
        $data['tipo'] = $_POST['tipo'] ?? null;
        $data['natureza'] = $_POST['natureza'] ?? null;
        $data['data_inicio'] = $_POST['data_inicio'] ?? null;
        $data['data_fim'] = $_POST['data_fim'] ?? null;
        $data['dias_afastamento'] = $_POST['dias_afastamento'] ?? null;
        $data['data_retorno'] = $_POST['data_retorno'] ?? null;
        $data['status'] = $_POST['status'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstAfastamentosRepository();
        $uploadService = new SstAnexosUploadService();
        if ($repo->update($id, $data)) {
            $uploadService->processDeletions($_POST['delete_anexos'] ?? [], 'afastamentos', $id);
            $uploadService->processUploads('afastamentos', $id);
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-afastamento/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-afastamento/' . $id);
        }
        exit;
    }
}
