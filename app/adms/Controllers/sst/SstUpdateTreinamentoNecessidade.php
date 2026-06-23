<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Models\Repository\SstTreinamentoNecessidadeRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

class SstUpdateTreinamentoNecessidade
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-necessidade');
            exit;
        }
        $repo = new SstTreinamentoNecessidadeRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-necessidade');
            exit;
        }
        $this->loadFormData();
        $cfg = require dirname(__DIR__, 4) . '/scripts/sst_entities_config.php';
        $this->data['entity'] = $cfg['treinamento_necessidade'];
        $pageElements = [
            'title_head' => 'Editar Necessidade de Treinamento SST',
            'menu' => 'sst-list-treinamento-necessidade',
            'buttonPermission' => ['SstUpdateTreinamentoNecessidade'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_necessidade/form', $this->data))->loadView();
    }

    private function loadFormData(): void
    {
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);
        $this->data['riscos'] = (new SstRiscosRepository())->getAll(1, 500);
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_treinamento_necessidade_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-treinamento-necessidade/' . $id);
            exit;
        }
        $data = [
            'adms_position_id' => $_POST['adms_position_id'] ?? null,
            'adms_department_id' => $_POST['adms_department_id'] ?? null,
            'adms_sst_risco_id' => $_POST['adms_sst_risco_id'] ?? null,
            'adms_sst_treinamento_id' => $_POST['adms_sst_treinamento_id'] ?? null,
            'validade_meses' => $_POST['validade_meses'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        if ((new SstTreinamentoNecessidadeRepository())->update($id, $data)) {
            SstPendenciasService::invalidateDashboardCache();
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-necessidade');
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-treinamento-necessidade/' . $id);
        }
        exit;
    }
}
