<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstRiscoExameRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateRiscoExame
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        $this->data['item'] = (new SstRiscoExameRepository())->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        $this->data['categorias_aso_selecionadas'] = (new SstRiscoExameRepository())->getCategoriasByRiscoExame(
            (int) ($this->data['item']['adms_sst_risco_id'] ?? 0),
            (int) ($this->data['item']['adms_sst_exame_id'] ?? 0)
        );
        $this->data['riscos'] = (new SstRiscosRepository())->getAll(1, 500);
        $this->data['exames'] = (new SstExamesRepository())->getAll(1, 500);
        $returnRiscoId = SstRiscoNavigationHelper::riscoIdFromRequest();
        if ($returnRiscoId <= 0) {
            $returnRiscoId = (int) ($this->data['item']['adms_sst_risco_id'] ?? 0);
        }
        if ($returnRiscoId > 0) {
            $this->data['return_risco_id'] = $returnRiscoId;
        }
        $pageElements = [
            'title_head' => 'Editar Exame por Risco - SST',
            'menu' => 'sst-list-riscos',
            'buttonPermission' => ['SstUpdateRiscoExame'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/risco_exame/form', $this->data))->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_risco_exame_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        $data = [
            'adms_sst_risco_id' => $_POST['adms_sst_risco_id'] ?? null,
            'adms_sst_exame_id' => $_POST['adms_sst_exame_id'] ?? null,
            'categorias_aso' => SstCategoriaAsoHelper::normalizeSelection(
                is_array($_POST['categorias_aso'] ?? null) ? $_POST['categorias_aso'] : []
            ),
            'periodicidade_meses' => $_POST['periodicidade_meses'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        $riscoId = (int) ($data['adms_sst_risco_id'] ?? SstRiscoNavigationHelper::riscoIdFromRequest());
        $exameId = (int) ($data['adms_sst_exame_id'] ?? 0);
        $oldRiscoId = (int) ($_POST['edit_origem_risco_id'] ?? 0);
        $oldExameId = (int) ($_POST['edit_origem_exame_id'] ?? 0);
        if ((new SstRiscoExameRepository())->syncVinculo(
            $riscoId,
            $exameId,
            $data['categorias_aso'],
            $data,
            $oldRiscoId > 0 ? $oldRiscoId : null,
            $oldExameId > 0 ? $oldExameId : null
        )) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'exames', 'sst-list-risco-exame');
        }
        $_SESSION['msg'] = 'Erro ao salvar registro.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-risco-exame/' . $id);
        exit;
    }
}
