<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;

class DeleteSapServiceLayerConnection
{
    public function index(): void
    {
        $redirect = $_ENV['URL_ADM'] . 'sap-service-layer-connections';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_sap_sl_conn_list', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token de segurança inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        $id = (int) ($_POST['connection_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['msg'] = 'Identificador inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new AdmsSapServiceLayerConnectionRepository();
        $ok = $repo->deleteById($id);
        $_SESSION['msg'] = $ok ? 'Conexão removida.' : 'Não foi possível remover a conexão.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $redirect);
        exit;
    }
}
