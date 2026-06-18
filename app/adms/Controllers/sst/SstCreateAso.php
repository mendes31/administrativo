<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAsoExamesRepository;
use App\adms\Models\Repository\SstAsosRepository;
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

class SstCreateAso
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $repo = new SstAsosRepository();
        $this->loadFormData();
        $this->data['item'] = [];
        if (!empty($_GET['adms_user_id'])) {
            $this->data['item']['adms_user_id'] = (int) $_GET['adms_user_id'];
        }
        $this->data['complementares'] = [];
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
            'title_head' => 'Create ASO - SST',
            'menu' => 'sst-list-asos',
            'buttonPermission' => ['SstCreateAso'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/asos/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_asos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-asos');
            exit;
        }
        $data = [];
        $data['adms_user_id'] = $_POST['adms_user_id'] ?? null;
        $data['adms_sst_exame_id'] = $_POST['adms_sst_exame_id'] ?? null;
        $data['adms_sst_medico_id'] = $_POST['adms_sst_medico_id'] ?? null;
        $data['tipo'] = $_POST['tipo'] ?? null;
        $data['data_realizacao'] = $_POST['data_realizacao'] ?? null;
        $data['data_validade'] = $_POST['data_validade'] ?? null;
        $data['resultado'] = $_POST['resultado'] ?? null;
        $data['restricoes'] = $_POST['restricoes'] ?? null;
        $data['clinica'] = $_POST['clinica'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstAsosRepository();
        $newId = $repo->create($data);
        if ($newId) {
            (new SstAsoExamesRepository())->syncForAso((int) $newId, $this->parseComplementaresFromPost());
            (new SstAnexosUploadService())->processUploads('asos', (int) $newId);
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-aso/' . $newId);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-aso');
        }
        exit;
    }

    /** @return list<array<string, mixed>> */
    private function parseComplementaresFromPost(): array
    {
        $rows = $_POST['complementares'] ?? [];
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['adms_sst_exame_id'])) {
                continue;
            }
            $out[] = [
                'adms_sst_exame_id' => (int) $row['adms_sst_exame_id'],
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'resultado' => $row['resultado'] ?? null,
            ];
        }

        return $out;
    }
}
