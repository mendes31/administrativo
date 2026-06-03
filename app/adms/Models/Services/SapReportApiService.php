<?php

namespace App\adms\Models\Services;

use Exception;
use App\adms\Models\Repository\AdmsSapApiConfigRepository;

/**
 * Cliente HTTP simples para consultar a API de relatórios SAP.
 */
class SapReportApiService
{
    private string $baseUrl;
    private int $timeout;
    private ?string $apiToken = null;

    public function __construct()
    {
        // Toda configuração vem da tela "Configuração da API SAP B1"
        $repo = new AdmsSapApiConfigRepository();
        $config = $repo->getConfig();

        if (empty($config) || (isset($config['is_active']) && (int)$config['is_active'] !== 1)) {
            throw new Exception(
                'Integração SAP B1 desativada ou não configurada. ' .
                'Acesse "Configurações -> Configuração SAP API" e salve os parâmetros de conexão.'
            );
        }

        // URL base cadastrada na tela (host + porta)
        $baseUrl = trim($config['base_url'] ?? '');
        $baseUrl = rtrim($baseUrl, '/');

        // A API esperada recebe as consultas em /query?sql=...
        // Se o admin já informou o endpoint /query completo, usar como está.
        // Caso contrário, o sistema completa automaticamente com /query.
        if ($baseUrl !== '' && !preg_match('~/query$~i', $baseUrl)) {
            $baseUrl .= '/query';
        }

        if ($baseUrl === '') {
            throw new Exception(
                'URL base da API SAP B1 não informada. ' .
                'Preencha o campo "URL Base da API" na tela de configuração SAP API.'
            );
        }

        $this->baseUrl = $baseUrl;

        // Timeout configurado em milissegundos na tela
        $timeoutMs = (int)($config['timeout_ms'] ?? 30000);
        if ($timeoutMs < 1000) {
            $timeoutMs = 1000;
        }
        // Armazenar em segundos para uso no cURL
        $this->timeout = (int)ceil($timeoutMs / 1000);

        // Token de autenticação opcional
        $apiToken = trim($config['api_token'] ?? '');
        $this->apiToken = $apiToken !== '' ? $apiToken : null;
    }

    public function execute(string $sql): array
    {
        error_log("🔷 SAP API Service - Método execute() chamado");
        
        if (empty($sql)) {
            error_log("❌ SAP API - SQL vazio recebido");
            throw new Exception('SQL não informado para a API SAP.');
        }

        // Aumentar limite de memória para respostas grandes (sem limite)
        ini_set('memory_limit', '-1'); // Sem limite de memória
        ini_set('max_execution_time', '600'); // 10 minutos para queries grandes
        
        error_log("🔷 SAP API - Iniciando execução");
        error_log("🔷 SAP API - SQL recebido (RAW): [" . $sql . "]");
        error_log("🔷 SAP API - SQL length: " . strlen($sql));

        // Limpar e normalizar o SQL
        $sql = trim($sql);
        $sql = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $sql);
        $sql = preg_replace('/\s{2,}/', ' ', $sql);
        $sql = trim($sql);
        error_log("🔷 SAP API - SQL após trim: [" . substr($sql, 0, 120) . "...]");
        
        // Remover números ou caracteres inválidos no início (comum em editores)
        $sql = preg_replace('/^[\d\s]+/i', '', $sql);
        $sql = trim($sql);
        error_log("🔷 SAP API - SQL após limpeza: [" . $sql . "]");
        
        // Validar que começa com SELECT ou WITH (CTE), comum em consultas SAP/HANA
        if (!preg_match('/^\s*(SELECT|WITH)\s+/i', $sql)) {
            error_log("❌ SAP API - Validação falhou! SQL não começa com SELECT ou WITH");
            error_log("❌ SAP API - Primeiros 50 chars: " . substr($sql, 0, 50));
            throw new Exception('SQL deve começar com SELECT ou WITH. SQL recebido: ' . substr($sql, 0, 50));
        }
        
        // Usar parâmetro 'sql' conforme documentação da API
        // Usar rawurlencode para preservar caracteres especiais corretamente
        $encodedSql = rawurlencode($sql);
        $url = $this->baseUrl . '?sql=' . $encodedSql;
        
        error_log("🔷 SAP API - Base URL: " . $this->baseUrl);
        error_log("🔷 SAP API - SQL final (antes de encode): " . $sql);
        error_log("🔷 SAP API - SQL encoded: " . substr($encodedSql, 0, 200) . '...');
        error_log("🔷 SAP API - URL completa: " . $url);
        
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Accept-Encoding: gzip, deflate', // Solicitar compressão
            'ngrok-skip-browser-warning: true', // Header para ngrok-free
            'User-Agent: PHP-SAP-Report-Client/1.0'
        ];

        // Se houver token configurado na tela, envia Authorization: Bearer <token>
        if (!empty($this->apiToken)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiToken;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            // Timeout geral para a requisição (em segundos)
            CURLOPT_TIMEOUT => max(60, $this->timeout), // mínimo 60s para queries grandes
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '', // Aceitar qualquer encoding (gzip, deflate)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            // Timeout de conexão em segundos (mais curto que o total da requisição)
            CURLOPT_CONNECTTIMEOUT => min(10, $this->timeout),
            CURLOPT_BUFFERSIZE => 16384 // Buffer maior para melhor performance
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);

        curl_close($ch);

        error_log("🔷 SAP API - HTTP Code: {$httpCode}");
        error_log("🔷 SAP API - Response length: " . strlen($response ?? ''));

        if ($response === false) {
            error_log("❌ SAP API - cURL Error: {$curlError}");
            throw new Exception('Falha ao consultar API SAP: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorDetails = $response ? substr($response, 0, 5000) : 'Sem resposta';
            error_log("===========================================");
            error_log("❌ SAP API - ERRO HTTP {$httpCode}");
            error_log("❌ SAP API - URL completa: " . $url);
            error_log("❌ SAP API - SQL original: [" . $sql . "]");
            error_log("❌ SAP API - SQL length: " . strlen($sql));
            error_log("❌ SAP API - SQL encoded: " . $encodedSql);
            error_log("❌ SAP API - Response completa: " . $errorDetails);
            error_log("❌ SAP API - cURL Error: " . ($curlError ?: 'Nenhum'));
            error_log("❌ SAP API - cURL Info URL: " . ($curlInfo['url'] ?? 'N/A'));
            error_log("===========================================");
            
            // Tentar extrair mensagem de erro se for JSON
            $errorJson = json_decode($response, true);
            $errorMessage = '';
            if (is_array($errorJson)) {
                $errorMessage = $errorJson['message'] ?? $errorJson['error'] ?? $errorJson['detail'] ?? $errorJson['title'] ?? '';
                // Se não encontrou mensagem específica, mostrar o JSON completo
                if (empty($errorMessage)) {
                    $errorMessage = json_encode($errorJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
            }
            
            if (empty($errorMessage)) {
                $errorMessage = $errorDetails ?: 'Sem detalhes do erro';
            }
            
            // Tratamento especial para erros conhecidos
            if ($httpCode === 403) {
                // Verificar se é erro de limite do ngrok
                if (stripos($errorMessage, 'ngrok') !== false || stripos($errorMessage, 'bandwidth') !== false || stripos($errorMessage, 'ERR_NGROK_725') !== false) {
                    $friendlyMessage = "⚠️ Limite de largura de banda do ngrok atingido.\n\n";
                    $friendlyMessage .= "A conexão com a API SAP está temporariamente indisponível porque a conta ngrok atingiu seu limite mensal de largura de banda.\n\n";
                    $friendlyMessage .= "Soluções:\n";
                    $friendlyMessage .= "• Aguardar a renovação do limite mensal\n";
                    $friendlyMessage .= "• Atualizar o plano do ngrok em https://dashboard.ngrok.com/billing\n";
                    $friendlyMessage .= "• Verificar com o administrador do sistema\n\n";
                    $friendlyMessage .= "Detalhes técnicos: " . $errorMessage;
                    throw new Exception($friendlyMessage);
                }
            }
            
            // Para HTTP 400, mostrar resposta completa
            if ($httpCode === 400) {
                $fullError = "Requisição inválida (HTTP 400).\n";
                $fullError .= "URL: {$url}\n";
                $fullError .= "SQL: {$sql}\n";
                $fullError .= "Resposta da API: {$errorMessage}";
                throw new Exception($fullError);
            }
            
            throw new Exception("API SAP retornou HTTP {$httpCode}: " . $errorMessage);
        }

        // Processar JSON de forma mais eficiente para respostas grandes
        $json = json_decode($response, true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = json_last_error_msg();
            error_log("❌ SAP API - JSON Error: " . $errorMsg);
            error_log("❌ SAP API - Response size: " . strlen($response) . " bytes");
            error_log("❌ SAP API - Response preview: " . substr($response, 0, 500));
            
            // Se o erro for por memória, aumentar ainda mais
            if (strpos($errorMsg, 'memory') !== false) {
                ini_set('memory_limit', '-1');
                error_log("⚠️ Erro de memória detectado, aumentando limite para ilimitado");
            }
            
            throw new Exception('Resposta inválida da API SAP: ' . $errorMsg);
        }

        // Se não for JSON, pode ser que a API retorne dados diretamente
        if ($json === null) {
            error_log("⚠️ SAP API - Resposta não é JSON, tentando parse direto");
            $json = ['data' => []];
        }

        // A API pode retornar array direto ou dentro de 'data'
        $data = $json['data'] ?? (isset($json[0]) && is_array($json) ? $json : []);
        if (!is_array($data)) {
            $data = [];
        }

        $rowCount = count($data);
        error_log("✅ SAP API - Sucesso: {$rowCount} registros retornados");

        return [
            'data' => $data,
            'rows_count' => $json['rows_count'] ?? count($data),
            'execution_time' => (float)($json['execution_time'] ?? 0.0),
            'raw' => $json
        ];
    }
}

