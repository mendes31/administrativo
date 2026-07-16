<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstEquipamentoNaoConformidadeService;

class SstEncerrarEquipamentoNaoConformidade
{
    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $id);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sst_encerrar_nc', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $id);
            exit;
        }

        $acaoId = (int) ($_POST['acao_id'] ?? 0);
        $result = (new SstEquipamentoNaoConformidadeService())->encerrarComAcao(
            $id,
            $acaoId,
            (int) ($_SESSION['user_id'] ?? 0)
        );

        $_SESSION['msg'] = $result['message'];
        $_SESSION['msg_type'] = !empty($result['ok']) ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $id);
        exit;
    }
}
