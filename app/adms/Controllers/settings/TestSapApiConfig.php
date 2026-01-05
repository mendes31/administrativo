<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsSapApiConfigRepository;
use App\adms\Helpers\SapApiService;

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

        $result = SapApiService::healthCheck($config);

        if ($result['success']) {
            $status = $result['status_code'] ?? 200;
            $duration = $result['duration_ms'] !== null ? number_format($result['duration_ms'], 2, ',', '.') : 'n/d';
            $_SESSION['msg'] = "✅ Conexão realizada com sucesso! (HTTP {$status} em {$duration} ms)";
            $_SESSION['msg_type'] = 'success';
        } else {
            $status = $result['status_code'] ? 'HTTP ' . $result['status_code'] : 'sem resposta';
            $error = $result['error'] ?: 'Erro desconhecido.';
            $_SESSION['msg'] = "❌ Falha ao conectar na API ({$status}). Detalhes: {$error}";
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sap-api-config');
        exit;
    }
}






