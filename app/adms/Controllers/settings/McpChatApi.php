<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\AdmsMcpApiConfigRepository;
use App\adms\Models\Repository\ButtonPermissionUserRepository;

class McpChatApi
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (empty($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'logout' => true,
                'message' => 'Sessão expirada. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar permissão via página lógica "McpChat"
        $buttonRepo = new ButtonPermissionUserRepository();
        $perms = $buttonRepo->buttonPermission(['McpChat']);
        if (empty($perms) || !in_array('McpChat', $perms, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para usar o chat MCP.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $message = trim($payload['message'] ?? '');

        if ($message === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Mensagem não informada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $configRepo = new AdmsMcpApiConfigRepository();
        $config = $configRepo->getConfig();

        if (empty($config) || empty($config['base_url']) || empty($config['is_active'])) {
            echo json_encode([
                'success' => false,
                'message' => 'API MCP não está configurada ou está desativada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $endpoint = rtrim((string) $config['base_url'], '/');

        // Piloto local (homologação): consultas RH no MySQL do Portal, sem servidor MCP externo.
        if (strcasecmp($endpoint, 'local:internal') === 0 || strcasecmp($endpoint, 'local://internal') === 0) {
            $agent = new \App\adms\Models\Services\InternalChat\LocalInternalChatAgent();
            $authContext = [
                'user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'allowed_tools' => [
                    'rh.count_active',
                    'rh.count_inactive',
                    'rh.count_terminated_in_month',
                    'rh.count_blocked',
                    'rh.count_active_by_department',
                    'report.list',
                    'report.run',
                    'rooms.list',
                    'rooms.agenda',
                    'rooms.my',
                    'rooms.reserve',
                    'rooms.cancel',
                ],
            ];
            $result = $agent->handle($message, $authContext);
            echo json_encode([
                'success' => true,
                'reply' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'payload' => $result,
                'http_code' => 200,
                'mode' => 'local:internal',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // O servidor MCP espera exatamente: { "message": "texto" }
        $requestBody = json_encode([
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POSTFIELDS => $requestBody,
            // Alguns proxies/túneis (como devtunnels) podem usar certificados não totalmente confiáveis.
            // Para este endpoint específico, desabilitamos a verificação estrita de SSL.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao contatar servidor MCP: ' . $curlError
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Se o servidor MCP retornou erro HTTP, repassar corpo e código para facilitar diagnóstico
        if ($httpCode >= 400) {
            echo json_encode([
                'success' => false,
                'message' => 'Servidor MCP retornou erro HTTP ' . $httpCode,
                'raw' => $responseBody,
                'http_code' => $httpCode,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Proxy transparente: repassa exatamente a resposta do MCP (texto ou JSON) para o frontend
        echo json_encode([
            'success' => true,
            'reply' => $responseBody,
            'http_code' => $httpCode,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

