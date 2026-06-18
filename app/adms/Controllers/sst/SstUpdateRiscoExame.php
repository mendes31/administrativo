<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
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
        $this->data['riscos'] = (new SstRiscosRepository())->getAll(1, 500);
        $this->data['exames'] = (new SstExamesRepository())->getAll(1, 500);
        $pageElements = [
            'title_head' => 'Editar Exame por Risco - SST',
            'menu' => 'sst-list-risco-exame',
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
            'categoria_aso' => $_POST['categoria_aso'] ?? null,
            'periodicidade_meses' => $_POST['periodicidade_meses'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        if ((new SstRiscoExameRepository())->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-risco-exame/' . $id);
        }
        exit;
    }
}
