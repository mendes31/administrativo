<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEsocialEventosRepository;

class SstMarkEsocialEnviado
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

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $repo = new SstEsocialEventosRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Evento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $ok = $repo->update($id, [
            'payload_json' => $item['payload_json'],
            'status' => 'Enviado',
            'protocolo' => trim((string) ($_POST['protocolo'] ?? '')) ?: null,
            'mensagem_retorno' => $_POST['mensagem_retorno'] ?? null,
            'data_geracao' => $item['data_geracao'] ?? date('Y-m-d H:i:s'),
            'data_envio' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['msg'] = $ok ? 'Evento marcado como enviado.' : 'Não foi possível atualizar o evento.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-esocial-evento/' . $id);
        exit;
    }
}
