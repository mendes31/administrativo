<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstRiscoTreinamentoRepository;

class SstSaveRiscoTreinamentos
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-riscos');
            exit;
        }

        $riscoId = (int) ($_POST['adms_sst_risco_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'sst-view-risco/' . $riscoId;

        if (!CSRFHelper::validateCSRFToken('sst_risco_treinamentos', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect . '#tab-treinamentos');
            exit;
        }

        $treinamentoIds = is_array($_POST['treinamentos'] ?? null) ? array_map('intval', $_POST['treinamentos']) : [];
        $obrigatorioPost = is_array($_POST['treinamentos_obrigatorio'] ?? null) ? $_POST['treinamentos_obrigatorio'] : [];
        $validadePost = is_array($_POST['treinamentos_validade'] ?? null) ? $_POST['treinamentos_validade'] : [];
        $treinamentoMap = [];
        foreach ($treinamentoIds as $treinamentoId) {
            if ($treinamentoId > 0) {
                $validade = trim((string) ($validadePost[$treinamentoId] ?? ''));
                $treinamentoMap[$treinamentoId] = [
                    'obrigatorio' => isset($obrigatorioPost[$treinamentoId]),
                    'validade_meses' => $validade !== '' ? (int) $validade : null,
                ];
            }
        }

        (new SstRiscoTreinamentoRepository())->syncTreinamentosForRisco($riscoId, $treinamentoMap);

        $_SESSION['msg'] = 'Treinamentos vinculados salvos com sucesso.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect . '#tab-treinamentos');
        exit;
    }
}
