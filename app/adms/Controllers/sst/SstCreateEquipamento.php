<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentoSettingsRepository;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEquipamentoCodigoService;
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
        $tipos = (new SstEquipamentoTiposRepository())->getAllActiveForSelect();
        $this->data['tipos'] = $tipos;
        $previews = [];
        $codigoService = new SstEquipamentoCodigoService();
        foreach ($tipos as $t) {
            $tid = (int) ($t['id'] ?? 0);
            if ($tid > 0) {
                $peek = $codigoService->peekNextCodigo($tid);
                if ($peek !== null) {
                    $previews[(string) $tid] = $peek;
                }
            }
        }
        $this->data['codigo_previews'] = $previews;
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['empresas_contratantes'] = UserFormHelper::empresaContratanteOptions();
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['periodicidades'] = SstEquipamentoPeriodicidadeHelper::options();
        $this->data['dias'] = SstEquipamentoPeriodicidadeHelper::dayOptions();
        $this->data['agentes_extintor'] = \App\adms\Helpers\SstEquipamentoCaracteristicasHelper::agentesExtintor();
        $this->data['capacidades_comuns'] = \App\adms\Helpers\SstEquipamentoCaracteristicasHelper::capacidadesComuns();
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
        $tipoId = (int) ($_POST['adms_sst_equipamento_tipo_id'] ?? 0);
        $tipo = $tipoId > 0 ? (new SstEquipamentoTiposRepository())->getById($tipoId) : null;
        if (!$tipo || trim((string) ($tipo['prefixo'] ?? '')) === '') {
            $_SESSION['msg'] = 'Selecione um grupo com prefixo cadastrado (3 caracteres).';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento');
            exit;
        }

        $data = $this->collectPost($tipo);
        $id = (new SstEquipamentosRepository())->create($data);
        if ($id) {
            if (!empty($tipo['controla_recarga']) && !empty($data['data_recarga'])) {
                (new SstEquipamentoRecargasRepository())->register([
                    'adms_sst_equipamento_id' => (int) $id,
                    'tipo_evento' => 'Recarga',
                    'data_recarga' => $data['data_recarga'],
                    'data_proxima_recarga' => $data['data_proxima_recarga'] ?? null,
                    'validade_meses' => (int) ($tipo['validade_recarga_meses'] ?? 12),
                    'observacao' => 'Registro inicial no cadastro do equipamento.',
                ]);
            }
            $equipamento = (new SstEquipamentosRepository())->getById((int) $id);
            $codigoGerado = (string) ($equipamento['codigo'] ?? '');
            $vistoriaGerada = (new SstEquipamentoVistoriaGeneratorService())->tryGenerateOnEquipamentoCreate((int) $id);
            $_SESSION['msg'] = $vistoriaGerada
                ? 'Equipamento ' . $codigoGerado . ' cadastrado e 1ª vistoria gerada para a competência atual.'
                : 'Equipamento ' . $codigoGerado . ' cadastrado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $id);
        } else {
            $_SESSION['msg'] = 'Erro ao salvar. Verifique se o grupo possui prefixo válido e tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento');
        }
        exit;
    }

    /** @return array<string, mixed> */
    private function collectPost(array $tipo): array
    {
        $dataRecarga = trim((string) ($_POST['data_recarga'] ?? ''));
        $dataProxima = trim((string) ($_POST['data_proxima_recarga'] ?? ''));
        $controla = !empty($tipo['controla_recarga']);

        if (!$controla) {
            $dataRecarga = '';
            $dataProxima = '';
        } elseif ($dataRecarga !== '' && $dataProxima === '') {
            $meses = (int) ($tipo['validade_recarga_meses'] ?? 12);
            $dataProxima = SstEquipamentoRecargaHelper::calcularProxima($dataRecarga, $meses > 0 ? $meses : 12);
        }

        return [
            'patrimonio' => $_POST['patrimonio'] ?? null,
            'adms_sst_equipamento_tipo_id' => (int) ($_POST['adms_sst_equipamento_tipo_id'] ?? 0),
            'adms_department_id' => $_POST['adms_department_id'] ?? null,
            'empresa_contratante' => UserFormHelper::normalizeEmpresaContratante($_POST['empresa_contratante'] ?? null),
            'localizacao' => $_POST['localizacao'] ?? null,
            'fabricante' => $_POST['fabricante'] ?? null,
            'modelo' => $_POST['modelo'] ?? null,
            'numero_serie' => $_POST['numero_serie'] ?? null,
            'capacidade' => $_POST['capacidade'] ?? null,
            'data_fabricacao' => $_POST['data_fabricacao'] ?? null,
            'data_recarga' => $dataRecarga !== '' ? $dataRecarga : null,
            'data_proxima_recarga' => $dataProxima !== '' ? $dataProxima : null,
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
