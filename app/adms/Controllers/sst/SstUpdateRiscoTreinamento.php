<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;
use App\adms\Models\Repository\SstRiscoTreinamentoRepository;

class SstUpdateRiscoTreinamento
{
    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            return;
        }
        if (!$id) {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'sst-list-riscos');
            exit;
        }
        $item = (new SstRiscoTreinamentoRepository())->getById((int) $id);
        if (!$item) {
            $_SESSION['msg'] = 'Registro não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'sst-list-riscos');
            exit;
        }
        header('Location: ' . SstRiscoNavigationHelper::viewUrl((int) ($item['adms_sst_risco_id'] ?? 0), 'treinamentos'));
        exit;
    }

    private function update(int $id): void
    {
        $repo = new SstRiscoTreinamentoRepository();
        $existing = $repo->getById($id);
        if (!CSRFHelper::validateCSRFToken('sst_risco_treinamento_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            SstRiscoNavigationHelper::redirectAfterMutation(
                (int) ($existing['adms_sst_risco_id'] ?? SstRiscoNavigationHelper::riscoIdFromRequest()),
                'treinamentos',
                'sst-list-riscos'
            );
        }
        $data = [
            'adms_sst_risco_id' => $_POST['adms_sst_risco_id'] ?? null,
            'adms_sst_treinamento_id' => $_POST['adms_sst_treinamento_id'] ?? null,
            'validade_meses' => $_POST['validade_meses'] ?? null,
            'obrigatorio' => isset($_POST['obrigatorio']),
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        $riscoId = (int) ($data['adms_sst_risco_id'] ?? $existing['adms_sst_risco_id'] ?? 0);
        if ($repo->update($id, $data)) {
            $_SESSION['msg'] = 'Registro salvo com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar registro.';
            $_SESSION['msg_type'] = 'danger';
        }
        SstRiscoNavigationHelper::redirectAfterMutation($riscoId, 'treinamentos', 'sst-list-riscos');
    }
}
