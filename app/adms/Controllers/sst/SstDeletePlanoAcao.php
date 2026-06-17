<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstPlanosAcaoRepository;

class SstDeletePlanoAcao
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = 'Método inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-acidentes');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $acidenteId = (int) ($_POST['adms_sst_acidente_id'] ?? 0);
        $redirect = $acidenteId > 0
            ? $_ENV['URL_ADM'] . 'sst-view-acidente/' . $acidenteId
            : $_ENV['URL_ADM'] . 'sst-list-acidentes';

        if (!$id || !CSRFHelper::validateCSRFToken('form_delete_sst_planos_acao', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new SstPlanosAcaoRepository();
        if ($repo->delete($id)) {
            $_SESSION['msg'] = 'Plano de ação excluído com sucesso.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Não foi possível excluir o plano de ação.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $redirect);
        exit;
    }
}
