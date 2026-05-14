<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;
use App\adms\Models\Services\SapGatewayHttpClient;

class TestSapServiceLayerConnection
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
        if (!$repo->getById($id)) {
            $_SESSION['msg'] = 'Conexão não encontrada.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $redirect);
            exit;
        }

        $result = SapGatewayHttpClient::ping($id);
        $_SESSION['msg'] = $result['message'];
        $_SESSION['msg_type'] = $result['success'] ? 'success' : 'danger';

        header('Location: ' . $redirect . '?edit=' . $id);
        exit;
    }
}
