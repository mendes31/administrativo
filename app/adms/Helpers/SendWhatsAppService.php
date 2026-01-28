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
        // Buscar configuração
        $repo = new AdmsWhatsAppConfigRepository();
        $config = $repo->getConfig();

        if (empty($config) || !$config['is_active']) {
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
        return match ($config['api_provider']) {
            'Evolution' => self::sendViaEvolution($phoneNumber, $message, $config, $options),
            'Twilio' => self::sendViaTwilio($phoneNumber, $message, $config, $options),
            'Meta' => self::sendViaMeta($phoneNumber, $message, $config, $options),
            default => ['success' => false, 'error' => 'Provedor desconhecido: ' . $config['api_provider']]
        };
    }

    /**
     * Enviar via Evolution API (open source, popular no Brasil)
     */
    private static function sendViaEvolution(string $phoneNumber, string $message, array $config, array $options): array
    {
        try {
            $url = rtrim($config['api_url'], '/') . '/message/sendText/' . $config['instance_name'];

            $payload = [
                'number' => $phoneNumber,
                'text' => $message
            ];

            // Log de debug da requisição
            error_log('WhatsApp Evolution - URL: ' . $url);
            error_log('WhatsApp Evolution - Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $config['api_key']
            ]);

            // Flag opcional para ignorar SSL em ambiente controlado (ex: desenvolvimento com ngrok)
            // Configure no .env: WHATSAPP_IGNORE_SSL=true  (ou false em produção)
            $ignoreSsl = filter_var($_ENV['WHATSAPP_IGNORE_SSL'] ?? false, FILTER_VALIDATE_BOOL);
            if ($ignoreSsl && str_starts_with($config['api_url'], 'https://')) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log('WhatsApp Evolution - HTTP Code: ' . $httpCode);
            error_log('WhatsApp Evolution - Response: ' . $response);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'message_id' => $data['key']['id'] ?? 'sent',
                    'provider' => 'Evolution'
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

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

