<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEquipamentoVistoriaGeneratorService;
use App\adms\Views\Services\LoadViewService;

class SstCreateEquipamento
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $this->loadFormData();
        $pageElements = [
            'title_head' => 'Novo equipamento - SST',
            'menu' => 'sst-list-equipamentos',
            'buttonPermission' => ['SstCreateEquipamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/form', $this->data))->loadView();
    }

    private function loadFormData(): void
    {
        $this->data['tipos'] = (new SstEquipamentoTiposRepository())->getAllActiveForSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['periodicidades'] = SstEquipamentoPeriodicidadeHelper::options();
        $this->data['dias'] = SstEquipamentoPeriodicidadeHelper::dayOptions();
        $this->data['settings_defaults'] = (new SstEquipamentoSettingsRepository())->get();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamentos_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }
        $data = $this->collectPost();
        $id = (new SstEquipamentosRepository())->create($data);
        if ($id) {
            $vistoriaGerada = (new SstEquipamentoVistoriaGeneratorService())->tryGenerateOnEquipamentoCreate((int) $id);
            $_SESSION['msg'] = $vistoriaGerada
                ? 'Equipamento cadastrado e 1ª vistoria gerada para a competência atual.'
                : 'Equipamento cadastrado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar (verifique código duplicado).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento');
        }
        exit;
    }

    /** @return array<string, mixed> */
    private function collectPost(): array
    {
        return [
            'codigo' => $_POST['codigo'] ?? '',
            'patrimonio' => $_POST['patrimonio'] ?? null,
            'adms_sst_equipamento_tipo_id' => (int) ($_POST['adms_sst_equipamento_tipo_id'] ?? 0),
            'adms_department_id' => $_POST['adms_department_id'] ?? null,
            'localizacao' => $_POST['localizacao'] ?? null,
            'fabricante' => $_POST['fabricante'] ?? null,
            'modelo' => $_POST['modelo'] ?? null,
            'numero_serie' => $_POST['numero_serie'] ?? null,
            'capacidade' => $_POST['capacidade'] ?? null,
            'data_fabricacao' => $_POST['data_fabricacao'] ?? null,
            'data_recarga' => $_POST['data_recarga'] ?? null,
            'data_proxima_recarga' => $_POST['data_proxima_recarga'] ?? null,
            'periodicidade_meses' => (int) ($_POST['periodicidade_meses'] ?? 1),
            'data_referencia_inspecao' => $_POST['data_referencia_inspecao'] ?? null,
            'dia_previsto_vistoria' => $_POST['dia_previsto_vistoria'] ?? null,
            'vistoria_automatica' => !empty($_POST['vistoria_automatica']),
            'responsavel_adms_user_id' => $_POST['responsavel_adms_user_id'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
    }
}
