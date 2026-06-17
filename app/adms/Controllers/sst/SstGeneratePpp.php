<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstPppService;

class SstGeneratePpp
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
            exit;
        }
        if (!CSRFHelper::validateCSRFToken('sst_generate_ppp', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
            exit;
        }

        $userId = (int) ($_POST['adms_user_id'] ?? 0);
        $redirect = $_POST['redirect'] ?? ($_ENV['URL_ADM'] . 'sst-list-ppp');
        if (!$userId) {
            $_SESSION['msg'] = 'Colaborador não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        try {
            $result = (new SstPppService())->gerarESalvar($userId, $_POST['observacoes'] ?? null);
            $_SESSION['msg'] = 'PPP versão ' . $result['versao'] . ' gerado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-ppp/' . $result['id']);
        } catch (\Throwable $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
        }
        exit;
    }
}
