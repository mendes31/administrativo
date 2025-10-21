<?php

namespace App\adms\Controllers\Services;

class RequestHelper
{
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        // 1) Priorizar cabeçalhos de proxy, validando e escolhendo o primeiro IP válido
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $value = (string)$_SERVER[$header];
                $parts = array_map('trim', explode(',', $value));
                foreach ($parts as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return self::normalizeLoopbackIp($ip);
                    }
                }
            }
        }

        // 2) Fallback para REMOTE_ADDR
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($remote) && filter_var($remote, FILTER_VALIDATE_IP)) {
            return self::normalizeLoopbackIp($remote);
        }

        // 3) Último recurso
        return '0.0.0.0';
    }

    public static function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Captura o hostname do equipamento cliente através do IP
     * 
     * @return string Hostname do cliente ou 'N/A' se não disponível
     */
    public static function getClientHostname(): string
    {
        $ip = self::getClientIp();
        
        // Não tentar resolver hostname para IPs inválidos ou localhost
        if (!$ip || $ip === '0.0.0.0' || $ip === '127.0.0.1' || $ip === '::1') {
            return 'localhost';
        }

        try {
            // Usar gethostbyaddr com @ para suprimir warnings
            // Timeout é controlado pela configuração default_socket_timeout do PHP
            $hostname = @gethostbyaddr($ip);
            
            // Se o hostname retornado for igual ao IP, significa que não foi resolvido
            if ($hostname === $ip || $hostname === false || empty($hostname)) {
                return 'N/A';
            }
            
            return $hostname;
            
        } catch (\Exception $e) {
            // Em caso de erro, retornar N/A
            return 'N/A';
        }
    }

    /**
     * Captura informações completas do cliente
     * 
     * @return array Array com todas as informações do cliente
     */
    public static function getClientInfo(): array
    {
        return [
            'ip' => self::getClientIp(),
            'hostname' => self::getClientHostname(),
            'user_agent' => self::getUserAgent(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? null,
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'real_ip' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
            'server_name' => $_SERVER['SERVER_NAME'] ?? null,
            'server_port' => $_SERVER['SERVER_PORT'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];
    }

    private static function normalizeLoopbackIp(string $ip): string
    {
        // Normalizar IPv6 loopback
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return '127.0.0.1';
        }
        return $ip;
    }
}


