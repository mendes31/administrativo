<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsSapApiConfigRepository;
use App\adms\Models\Services\SapReportApiService;
use Exception;

class TestSapApiConfig
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        $repo = new AdmsSapApiConfigRepository();
        $config = $repo->getConfig();

        if (empty($config)) {
            $_SESSION['msg'] = 'Configure a API primeiro antes de realizar o teste.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
            exit;
        }

        try {
            // Usa o mesmo cliente de relatórios, executando um SELECT simples compatível com SAP HANA
            $service = new SapReportApiService();
            $start = microtime(true);
            $result = $service->execute('SELECT 1 FROM DUMMY');
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            if (!empty($result['data'])) {
                $_SESSION['msg'] = "✅ Conexão realizada com sucesso! (SELECT 1 FROM DUMMY em {$durationMs} ms)";
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['msg'] = "⚠️ API respondeu, mas não retornou dados para SELECT 1 FROM DUMMY. Verifique a implementação da API.";
                $_SESSION['msg_type'] = 'warning';
            }
        } catch (Exception $e) {
            $_SESSION['msg'] = "❌ Falha ao conectar na API. Detalhes: " . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
        exit;
    }
}

