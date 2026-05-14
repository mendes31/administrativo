<?php

declare(strict_types=1);

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SapServiceLayerConnections
{
    public function index(): void
    {
        $repo = new AdmsSapServiceLayerConnectionRepository();
        $list = $repo->listForAdmin();
        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $editRow = ($editId > 0) ? $repo->getById($editId) : null;
        if ($editId > 0 && !$editRow) {
            $_SESSION['msg'] = 'Conexão não encontrada.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-service-layer-connections');
            exit;
        }

        $data = [
            'title_head' => 'API SAP (integração)',
            'menu' => 'sap-service-layer-connections',
            'buttonPermission' => [
                'SapServiceLayerConnections',
                'SaveSapServiceLayerConnection',
                'DeleteSapServiceLayerConnection',
                'TestSapServiceLayerConnection',
            ],
            'connections' => $list,
            'edit_row' => $editRow,
            'csrf_token_list' => CSRFHelper::generateCSRFToken('form_sap_sl_conn_list'),
            'csrf_token_form' => CSRFHelper::generateCSRFToken('form_sap_sl_conn_save'),
        ];

        if ($editRow) {
            $returnUrl = $_ENV['URL_ADM'] . 'sap-service-layer-connections?edit=' . (int) $editRow['id'];
            $data['log_resumo'] = LogResumoService::getResumo('adms_sap_service_layer_connections', (int) $editRow['id'], $returnUrl);
        }

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/sapServiceLayerConnections', $data);
        $loadView->loadView();
    }
}
