<?php

namespace App\adms\Helpers;

/**
 * Helper para coleta de informações de auditoria LGPD
 * 
 * Este helper coleta informações técnicas necessárias para auditoria
 * de consentimentos, seguindo boas práticas de ferramentas profissionais.
 */
class LgpdAuditHelper
{
    /**
     * Coleta todas as informações técnicas disponíveis do ambiente
     * 
     * @return array Dados técnicos coletados
     */
    public static function collectTechnicalData(): array
    {
        return [
            'ip_address' => self::getIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? null,
            'origin_url' => self::getOriginUrl(),
            'session_id' => session_id() ?: null,
            'browser_language' => self::getBrowserLanguage(),
            'timestamp_milliseconds' => (int)(microtime(true) * 1000),
            'collection_method' => self::detectCollectionMethod(),
        ];
    }

    /**
     * Obtém o endereço IP real do cliente
     * Considera proxies e load balancers
     * 
     * @return string|null
     */
    private static function getIpAddress(): ?string
    {
        // Verificar headers de proxy primeiro
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Proxy padrão
            'HTTP_CLIENT_IP',            // Outros proxies
            'REMOTE_ADDR',               // IP direto
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Se houver múltiplos IPs (X-Forwarded-For), pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Se não encontrou IP público, retornar REMOTE_ADDR mesmo que seja privado
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Obtém a URL de origem (página atual)
     * 
     * @return string|null
     */
    private static function getOriginUrl(): ?string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . $host . $uri;
    }

    /**
     * Obtém o idioma do navegador
     * 
     * @return string|null
     */
    private static function getBrowserLanguage(): ?string
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        // Pegar o primeiro idioma da lista
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $primaryLang = trim(explode(';', $languages[0])[0]);
        
        return $primaryLang ?: null;
    }

    /**
     * Detecta o método de coleta baseado no contexto
     * 
     * @return string
     */
    private static function detectCollectionMethod(): string
    {
        // Se está na rota de login, é sistema_login
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'lgpd-consentimento-login') !== false) {
            return 'sistema_login';
        }
        
        // Se é uma requisição API (JSON)
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return 'api';
        }
        
        // Se tem parâmetro específico de email
        if (!empty($_GET['source']) && $_GET['source'] === 'email') {
            return 'email';
        }
        
        // Se tem parâmetro específico de SMS
        if (!empty($_GET['source']) && $_GET['source'] === 'sms') {
            return 'sms';
        }
        
        // Padrão: formulário web
        return 'web_form';
    }

    /**
     * Gera hash SHA-256 do consentimento para garantir integridade
     * 
     * @param array $consentData Dados do consentimento
     * @return string Hash SHA-256
     */
    public static function generateConsentHash(array $consentData): string
    {
        // Dados que compõem a "assinatura" do consentimento
        $dataToHash = [
            $consentData['titular_email'] ?? '',
            $consentData['finalidade'] ?? '',
            $consentData['data_consentimento'] ?? '',
            $consentData['versao_termo'] ?? '',
            $consentData['status'] ?? 'Ativo',
            $consentData['canal'] ?? '',
        ];
        
        // Ordenar para garantir consistência
        sort($dataToHash);
        
        // Gerar hash
        return hash('sha256', json_encode($dataToHash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Gera fingerprint do dispositivo (hash único baseado em características)
     * 
     * @return string Hash do dispositivo
     */
    public static function generateDeviceFingerprint(): string
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_POST['screen_resolution'] ?? $_GET['screen_resolution'] ?? '',
            $_SERVER['HTTP_ACCEPT'] ?? '',
        ];
        
        return hash('sha256', implode('|', $data));
    }

    /**
     * Obtém informações de geolocalização básica (apenas país)
     * 
     * NOTA: Para implementação completa, usar serviço como MaxMind GeoIP2
     * 
     * @param string|null $ipAddress Endereço IP
     * @return array|null ['country' => 'BR', 'region' => 'SP', 'city' => 'São Paulo']
     */
    public static function getGeolocation(?string $ipAddress = null): ?array
    {
        if (empty($ipAddress)) {
            $ipAddress = self::getIpAddress();
        }
        
        if (empty($ipAddress)) {
            return null;
        }
        
        // TODO: Implementar integração com serviço de geolocalização
        // Por enquanto, retornar null
        // Exemplo de serviços: MaxMind GeoIP2, IP2Location, ipapi.co
        
        return null;
    }

    /**
     * Valida se um hash de consentimento está correto
     * 
     * @param int $consentId ID do consentimento
     * @param string $expectedHash Hash esperado
     * @return bool True se o hash está correto
     */
    public static function validateConsentHash(int $consentId, string $expectedHash): bool
    {
        // Buscar dados do consentimento
        $repo = new \App\adms\Models\Repository\LgpdConsentimentosRepository();
        $consent = $repo->getConsentimentoById($consentId);
        
        if (!$consent) {
            return false;
        }
        
        // Gerar hash atual
        $currentHash = self::generateConsentHash($consent);
        
        // Comparar
        return hash_equals($expectedHash, $currentHash);
    }
}

