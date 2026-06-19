<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\SstCidsRepository;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstMedicosRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateEpi
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
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $repo = new SstEpisRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $this->loadFormData();
        $this->data['entity'] = array (
  'table' => 'adms_sst_epis',
  'singular' => 'EPI',
  'plural' => 'EPIs',
  'prefix' => 'Epi',
  'url' => 'epi',
  'menu' => 'sst-list-epis',
  'icon' => 'fa-hard-hat',
  'type' => 'catalog',
  'fields' => 
  array (
    'nome' => 
    array (
      'label' => 'Nome',
      'type' => 'text',
      'required' => true,
    ),
    'descricao' => 
    array (
      'label' => 'Descrição',
      'type' => 'textarea',
    ),
    'ca_numero' => 
    array (
      'label' => 'Nº CA',
      'type' => 'text',
    ),
    'ca_validade' => 
    array (
      'label' => 'Validade CA',
      'type' => 'date',
    ),
    'estoque_atual' => 
    array (
      'label' => 'Estoque atual',
      'type' => 'number',
    ),
    'estoque_minimo' => 
    array (
      'label' => 'Estoque mínimo',
      'type' => 'number',
    ),
    'periodicidade_troca_dias' => 
    array (
      'label' => 'Troca (dias)',
      'type' => 'number',
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
    2 => 'ca_numero',
    3 => 'estoque_atual',
    4 => 'status',
  ),
);
        $pageElements = [
            'title_head' => 'Update EPI - SST',
            'menu' => 'sst-list-epis',
            'buttonPermission' => ['SstUpdateEpi'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epis/form', $this->data))->loadView();
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
        if (!CSRFHelper::validateCSRFToken('sst_epis_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
            exit;
        }
        $data = [];
        $data['nome'] = $_POST['nome'] ?? null;
        $data['descricao'] = $_POST['descricao'] ?? null;
        $data['ca_numero'] = $_POST['ca_numero'] ?? null;
        $data['ca_validade'] = $_POST['ca_validade'] ?? null;
        $data['estoque_minimo'] = $_POST['estoque_minimo'] ?? null;
        $data['periodicidade_troca_dias'] = $_POST['periodicidade_troca_dias'] ?? null;
        $data['status'] = $_POST['status'] ?? null;

        $repo = new SstEpisRepository();
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-epi/' . $id);
        }
        exit;
    }
}
