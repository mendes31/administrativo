<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstEsocialPayloadService;

class SstGenerateEsocialEvento
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sst_esocial_actions', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $tipo = (string) ($_POST['tipo_evento'] ?? '');
        $origemTabela = (string) ($_POST['origem_tabela'] ?? '');
        $origemId = (int) ($_POST['origem_id'] ?? 0);
        $redirect = $_POST['redirect'] ?? ($_ENV['URL_ADM'] . 'sst-list-esocial-eventos');

        try {
            $result = (new SstEsocialPayloadService())->gerarOuAtualizar($tipo, $origemTabela, $origemId);
            $_SESSION['msg'] = 'Rascunho eSocial ' . $result['acao'] . ' (#' . $result['id'] . '). Não transmite ao governo.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-esocial-evento/' . $result['id']);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }
    }
}
