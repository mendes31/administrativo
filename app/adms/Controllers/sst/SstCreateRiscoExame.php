<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstExamesRepository;
use App\adms\Models\Repository\SstRiscoExameRepository;
use App\adms\Models\Repository\SstRiscosRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateRiscoExame
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $this->data['item'] = [];
        $this->data['riscos'] = (new SstRiscosRepository())->getAll(1, 500);
        $this->data['exames'] = (new SstExamesRepository())->getAll(1, 500);
        $pageElements = [
            'title_head' => 'Novo Exame por Risco - SST',
            'menu' => 'sst-list-risco-exame',
            'buttonPermission' => ['SstCreateRiscoExame'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/risco_exame/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_risco_exame_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
            exit;
        }
        $data = $this->parsePost();
        $newId = (new SstRiscoExameRepository())->create($data);
        if ($newId) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-risco-exame');
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-risco-exame');
        }
        exit;
    }

    private function parsePost(): array
    {
        return [
            'adms_sst_risco_id' => $_POST['adms_sst_risco_id'] ?? null,
            'adms_sst_exame_id' => $_POST['adms_sst_exame_id'] ?? null,
            'categoria_aso' => $_POST['categoria_aso'] ?? null,
            'periodicidade_meses' => $_POST['periodicidade_meses'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
    }
}
