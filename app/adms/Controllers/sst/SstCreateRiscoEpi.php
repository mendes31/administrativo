<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstRiscoEpiRepository;

class SstCreateRiscoEpi
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $riscoId = SstRiscoNavigationHelper::riscoIdFromRequest();
        if ($riscoId > 0) {
            header('Location: ' . SstRiscoNavigationHelper::viewUrl($riscoId, 'epis'));
            exit;
        }
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'sst-list-riscos');
        exit;
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_risco_epi_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            SstRiscoNavigationHelper::redirectAfterMutation(SstRiscoNavigationHelper::riscoIdFromRequest(), 'epis', 'sst-list-riscos');
        }
        $data = [
            'adms_sst_risco_id' => $_POST['adms_sst_risco_id'] ?? null,
            'adms_sst_epi_id' => $_POST['adms_sst_epi_id'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        $riscoId = (int) ($data['adms_sst_risco_id'] ?? SstRiscoNavigationHelper::riscoIdFromRequest());
        if ((new SstRiscoEpiRepository())->create($data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'epis', 'sst-list-riscos');
    }
}
