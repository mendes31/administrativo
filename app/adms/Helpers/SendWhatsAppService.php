<?php

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsWhatsAppConfigRepository;

/**
 * Service para enviar mensagens WhatsApp
 * Suporta múltiplas APIs: Evolution, Twilio, Meta (oficial)
 *
 * @package App\adms\Helpers
 * @author Rafael Mendes
 */
class SendWhatsAppService
{
    /**
     * Enviar mensagem WhatsApp
     *
     * @param string $phoneNumber Número com DDI (ex: 5541999887766)
     * @param string $message Texto da mensagem
     * @param array $options Opções adicionais (mídia, botões, etc)
     * @return array ['success' => bool, 'message_id' => string, 'error' => string]
     */
    public static function sendMessage(string $phoneNumber, string $message, array $options = []): array
    {
        try {
            // Buscar configuração com proteção a erros de banco/migração
            $repo = new AdmsWhatsAppConfigRepository();
            $config = $repo->getConfig();
        } catch (\Throwable $e) {
            error_log('WhatsApp - Erro ao carregar configuração: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao carregar configuração de WhatsApp. Verifique as migrações e o banco de dados.'
            ];
        }

        if (empty($config) || !($config['is_active'] ?? 0)) {
            return [
                'success' => false,
                'error' => 'WhatsApp não configurado ou desativado'
            ];
        }

        // Limpar número (apenas dígitos)
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Garantir DDI correto (Brasil = 55)
        // Número brasileiro completo: 13 dígitos (55 + DD + 9 dígitos) ou 12 dígitos (55 + DD + 8 dígitos)
        
        // Se tem 10 ou 11 dígitos, é número local (DDD + número) - adicionar DDI 55
        if (strlen($phoneNumber) === 10 || strlen($phoneNumber) === 11) {
            $phoneNumber = '55' . $phoneNumber;
        }
        
        // Se tem 12 ou 13 dígitos e NÃO começa com 55, algo está errado
        if ((strlen($phoneNumber) === 12 || strlen($phoneNumber) === 13) && !str_starts_with($phoneNumber, '55')) {
            // Tentar adicionar DDI mesmo assim
            $phoneNumber = '55' . $phoneNumber;
        }
        
        // Log para debug
        error_log("WhatsApp - Número formatado: " . $phoneNumber . " (length: " . strlen($phoneNumber) . ")");

        // Escolher provedor
        try {
            return match ($config['api_provider']) {
                'Evolution' => self::sendViaEvolution($phoneNumber, $message, $config, $options),
                'Twilio'    => self::sendViaTwilio($phoneNumber, $message, $config, $options),
                'Meta'      => self::sendViaMeta($phoneNumber, $message, $config, $options),
                default     => ['success' => false, 'error' => 'Provedor desconhecido: ' . $config['api_provider']]
            };
        } catch (\Throwable $e) {
            error_log('WhatsApp - Erro inesperado ao enviar mensagem: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Erro inesperado ao enviar mensagem WhatsApp: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica se a URL aponta para túnel ngrok (requer header especial em requisições server-side).
     */
    private static function isNgrokUrl(string $url): bool
    {
        return stripos($url, 'ngrok') !== false;
    }

    /**
     * Headers padrão para chamadas à Evolution API.
     */
    private static function getEvolutionHeaders(array $config): array
    {
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . ($config['api_key'] ?? ''),
        ];

        if (self::isNgrokUrl((string)($config['api_url'] ?? ''))) {
            $headers[] = 'ngrok-skip-browser-warning: true';
        }

        return $headers;
    }

    /**
     * Requisição HTTP genérica à Evolution API.
     *
     * @return array{ok: bool, http_code: int, body: string, curl_error: string|null}
     */
    private static function evolutionHttpRequest(string $method, string $url, array $config, ?array $payload = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::getEvolutionHeaders($config));

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $connectTimeout = (int)($_ENV['WHATSAPP_CONNECT_TIMEOUT'] ?? 10);
        $timeout        = (int)($_ENV['WHATSAPP_TIMEOUT'] ?? 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $ignoreSsl = filter_var($_ENV['WHATSAPP_IGNORE_SSL'] ?? false, FILTER_VALIDATE_BOOL);
        if ($ignoreSsl || self::isNgrokUrl((string)($config['api_url'] ?? ''))) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = $body === false ? curl_error($ch) : null;
        curl_close($ch);

        return [
            'ok' => $body !== false,
            'http_code' => $httpCode,
            'body' => $body !== false ? (string)$body : '',
            'curl_error' => $curlError,
        ];
    }

    /**
     * Consulta estado da instância WhatsApp na Evolution API.
     */
    public static function getEvolutionConnectionState(array $config): array
    {
        $instanceName = trim((string)($config['instance_name'] ?? ''));
        if ($instanceName === '') {
            return ['success' => false, 'state' => 'unknown', 'error' => 'Nome da instância não configurado.'];
        }

        $url = rtrim((string)$config['api_url'], '/')
            . '/instance/connectionState/' . rawurlencode($instanceName);

        $result = self::evolutionHttpRequest('GET', $url, $config);

        if (!$result['ok']) {
            return [
                'success' => false,
                'state' => 'unknown',
                'error' => 'Falha ao consultar estado da instância: ' . ($result['curl_error'] ?? 'erro de rede'),
            ];
        }

        $data = json_decode($result['body'], true);
        $instanceNode = is_array($data['instance'] ?? null) ? $data['instance'] : [];
        $state = strtolower((string)(
            $data['state']
            ?? $data['connectionStatus']
            ?? $instanceNode['state']
            ?? $instanceNode['status']
            ?? $instanceNode['connectionStatus']
            ?? ''
        ));
        // Evolution 2.x às vezes retorna "connected" no Manager e "open" na API
        if ($state === 'connected') {
            $state = 'open';
        }

        if ($state === '') {
            return [
                'success' => $result['http_code'] >= 200 && $result['http_code'] < 300,
                'state' => 'unknown',
                'http_code' => $result['http_code'],
                'raw' => $data,
                'error' => $result['http_code'] >= 400
                    ? 'HTTP ' . $result['http_code'] . ' ao consultar instância. Verifique o nome da instância.'
                    : null,
            ];
        }

        return [
            'success' => true,
            'state' => $state,
            'http_code' => $result['http_code'],
            'raw' => $data,
        ];
    }

    /**
     * Traduz erros comuns da Evolution API para mensagem acionável.
     */
    private static function formatEvolutionError(int $httpCode, string $response, array $config): string
    {
        if (stripos($response, 'Connection Closed') !== false) {
            $managerUrl = rtrim((string)($config['api_url'] ?? ''), '/') . '/manager';
            return 'Evolution retornou "Connection Closed" para a instância "' . ($config['instance_name'] ?? '') . '". '
                . 'Mesmo com o Manager mostrando conectado, o socket WhatsApp pode estar inconsistente. '
                . 'Tente: (1) Desconectar e conectar de novo no Manager (' . $managerUrl . '); '
                . '(2) Reiniciar o container da Evolution API; '
                . '(3) Conferir se o nome da instância no cadastro é exatamente o do Manager.';
        }

        if (stripos($response, 'textMessage') !== false) {
            return 'Formato de mensagem incompatível com a versão da Evolution API. Atualize o sistema ou contate o suporte.';
        }

        if (stripos($response, 'not found') !== false || $httpCode === 404) {
            return 'Instância "' . ($config['instance_name'] ?? '') . '" não encontrada na Evolution API. '
                . 'Confira o nome exato em Manager → Instâncias (diferencia maiúsculas/minúsculas).';
        }

        if ($httpCode === 401 || stripos($response, 'Unauthorized') !== false) {
            return 'API Key inválida. Use a mesma chave configurada em AUTHENTICATION_API_KEY na Evolution API.';
        }

        return 'HTTP ' . $httpCode . ': ' . $response;
    }

    /**
     * Enviar via Evolution API (open source, popular no Brasil)
     */
    /**
     * Monta payloads compatíveis com Evolution API v1 (text) e v2.3+ (textMessage).
     *
     * @return list<array<string, mixed>>
     */
    private static function buildEvolutionTextPayloads(string $phoneNumber, string $message): array
    {
        return [
            [
                'number' => $phoneNumber,
                'textMessage' => ['text' => $message],
                'options' => ['linkPreview' => false],
            ],
            [
                'number' => $phoneNumber,
                'text' => $message,
            ],
        ];
    }

    /**
     * Tenta enviar texto com mais de um formato de payload (v2 e legado).
     */
    private static function sendEvolutionTextWithFallback(string $url, array $config, string $phoneNumber, string $message): array
    {
        $lastResult = ['http_code' => 0, 'body' => ''];

        foreach (self::buildEvolutionTextPayloads($phoneNumber, $message) as $payload) {
            error_log('WhatsApp Evolution - Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
            $result = self::evolutionHttpRequest('POST', $url, $config, $payload);
            $lastResult = $result;

            if (!$result['ok']) {
                continue;
            }

            error_log('WhatsApp Evolution - HTTP Code: ' . $result['http_code']);
            error_log('WhatsApp Evolution - Response: ' . $result['body']);

            if ($result['http_code'] >= 200 && $result['http_code'] < 300) {
                $data = json_decode($result['body'], true);
                return [
                    'success' => true,
                    'message_id' => $data['key']['id'] ?? 'sent',
                    'provider' => 'Evolution',
                ];
            }

            // Só tenta formato legado se o erro indicar incompatibilidade de schema
            $body = $result['body'];
            $retryable = stripos($body, 'textMessage') !== false
                || stripos($body, '"text"') !== false
                || stripos($body, 'requires property') !== false;
            if (!$retryable) {
                break;
            }
        }

        if (!$lastResult['ok']) {
            return [
                'success' => false,
                'error' => 'Falha de conexão com a API WhatsApp (Evolution): ' . ($lastResult['curl_error'] ?? 'erro de rede'),
            ];
        }

        return [
            'success' => false,
            'error' => self::formatEvolutionError($lastResult['http_code'], $lastResult['body'], $config),
        ];
    }

    private static function sendViaEvolution(string $phoneNumber, string $message, array $config, array $options): array
    {
        try {
            $connection = self::getEvolutionConnectionState($config);
            if (!empty($connection['state'])) {
                error_log('WhatsApp Evolution - Connection state (API): ' . $connection['state']);
                if (($connection['state'] ?? '') === 'close') {
                    error_log('WhatsApp Evolution - Aviso: API reporta state=close; tentando envio mesmo assim.');
                }
            }

            $instanceName = rawurlencode((string)($config['instance_name'] ?? ''));
            $url = rtrim((string)$config['api_url'], '/') . '/message/sendText/' . $instanceName;

            error_log('WhatsApp Evolution - URL: ' . $url);

            return self::sendEvolutionTextWithFallback($url, $config, $phoneNumber, $message);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar via Twilio API
     */
    private static function sendViaTwilio(string $phoneNumber, string $message, array $config, array $options): array
    {
        try {
            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $config['api_key'] . '/Messages.json';

            $payload = [
                'From' => 'whatsapp:+' . $config['phone_number'],
                'To' => 'whatsapp:+' . $phoneNumber,
                'Body' => $message
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_USERPWD, $config['api_key'] . ':' . $config['api_token']);

            $connectTimeout = (int)($_ENV['WHATSAPP_CONNECT_TIMEOUT'] ?? 10);
            $timeout        = (int)($_ENV['WHATSAPP_TIMEOUT'] ?? 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error  = curl_error($ch);
                $errno  = curl_errno($ch);
                curl_close($ch);
                error_log('WhatsApp Twilio - cURL error (' . $errno . '): ' . $error);
                return [
                    'success' => false,
                    'error'   => 'Falha de conexão com a API WhatsApp (Twilio): ' . $error,
                ];
            }

            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message_id' => $data['sid'] ?? 'sent',
                    'provider' => 'Twilio'
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $httpCode . ': ' . $response
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar via Meta WhatsApp Business API (oficial)
     */
    private static function sendViaMeta(string $phoneNumber, string $message, array $config, array $options): array
    {
        try {
            $url = $config['api_url'] . '/messages';

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => ['body' => $message]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_token']
            ]);

            $connectTimeout = (int)($_ENV['WHATSAPP_CONNECT_TIMEOUT'] ?? 10);
            $timeout        = (int)($_ENV['WHATSAPP_TIMEOUT'] ?? 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error  = curl_error($ch);
                $errno  = curl_errno($ch);
                curl_close($ch);
                error_log('WhatsApp Meta - cURL error (' . $errno . '): ' . $error);
                return [
                    'success' => false,
                    'error'   => 'Falha de conexão com a API WhatsApp (Meta): ' . $error,
                ];
            }

            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? 'sent',
                    'provider' => 'Meta'
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $httpCode . ': ' . $response
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

