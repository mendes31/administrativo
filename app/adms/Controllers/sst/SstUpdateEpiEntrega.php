<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstLegacyEpiEntregaGuard;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateEpiEntrega
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        SstLegacyEpiEntregaGuard::denyAndRedirect();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            $_SESSION['msg'] = 'ID não informado.';
            $_SESSION['msg_type'] = 'danger';
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
        $this->loadFormData();
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
            'title_head' => 'Update Entrega de EPI - SST',
            'menu' => 'sst-list-epi-entregas',
            'buttonPermission' => ['SstUpdateEpiEntrega'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_entregas/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_epi_entregas_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-entregas');
            exit;
        }
        $data = [];
        $data['adms_user_id'] = $_POST['adms_user_id'] ?? null;
        $data['adms_sst_epi_id'] = $_POST['adms_sst_epi_id'] ?? null;
        $data['tipo_movimento'] = $_POST['tipo_movimento'] ?? null;
        $data['quantidade'] = $_POST['quantidade'] ?? null;
        $data['data_movimento'] = $_POST['data_movimento'] ?? null;
        $data['data_prevista_troca'] = $_POST['data_prevista_troca'] ?? null;
        $data['termo_assinado'] = isset($_POST['termo_assinado']);
        $data['observacoes'] = $_POST['observacoes'] ?? null;

        $repo = new SstEpiEntregasRepository();
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-entrega/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-epi-entrega/' . $id);
        }
        exit;
    }
}
