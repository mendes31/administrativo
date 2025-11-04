<?php

namespace App\adms\Models\Services;

/**
 * SAP Business One Service Layer Client
 * 
 * Conecta ao SAP B1 via Service Layer (REST API) para executar queries
 */
class SapB1ServiceLayer
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $companyDB;
    private ?string $sessionId = null;
    private ?string $routeId = null;
    private array $cookies = [];
    
    public function __construct()
    {
        $this->baseUrl = $_ENV['SAP_SL_URL'] ?? '';
        $this->username = $_ENV['SAP_SL_USERNAME'] ?? '';
        $this->password = $_ENV['SAP_SL_PASSWORD'] ?? '';
        $this->companyDB = $_ENV['SAP_SL_COMPANY'] ?? '';
    }
    
    /**
     * Login na Service Layer
     */
    public function login(): bool
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/Login';
            
            $data = [
                'CompanyDB' => $this->companyDB,
                'UserName' => $this->username,
                'Password' => $this->password
            ];
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("SAP SL Login falhou: HTTP $httpCode");
                return false;
            }
            
            // Extrair cookies e session
            preg_match_all('/Set-Cookie: (.*?);/i', $response, $matches);
            if (isset($matches[1])) {
                foreach ($matches[1] as $cookie) {
                    $this->cookies[] = $cookie;
                    
                    // Capturar B1SESSION e ROUTEID
                    if (strpos($cookie, 'B1SESSION=') === 0) {
                        $this->sessionId = str_replace('B1SESSION=', '', $cookie);
                    }
                    if (strpos($cookie, 'ROUTEID=') === 0) {
                        $this->routeId = str_replace('ROUTEID=', '', $cookie);
                    }
                }
            }
            
            return !empty($this->sessionId);
            
        } catch (\Exception $e) {
            error_log("Erro ao conectar SAP Service Layer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executar query SQL via Service Layer
     */
    public function executeQuery(string $sql): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return [
                'success' => false,
                'error' => 'Não foi possível conectar ao SAP Service Layer'
            ];
        }
        
        try {
            $url = rtrim($this->baseUrl, '/') . '/SQLQueries(\'CustomQuery\')/List';
            
            $data = ['Query' => $sql];
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Cookie: B1SESSION=' . $this->sessionId . '; ROUTEID=' . $this->routeId
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => "Erro HTTP $httpCode ao executar query"
                ];
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['value'])) {
                return [
                    'success' => true,
                    'data' => $result['value'],
                    'rows_count' => count($result['value'])
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Resposta inválida da Service Layer'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar itens (exemplo de endpoint específico)
     */
    public function getItems(array $filters = [], int $top = 20): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }
        
        $url = rtrim($this->baseUrl, '/') . '/Items';
        
        // Adicionar filtros OData
        $params = ['$top' => $top];
        if (!empty($filters['filter'])) {
            $params['$filter'] = $filters['filter'];
        }
        if (!empty($filters['select'])) {
            $params['$select'] = $filters['select'];
        }
        
        $url .= '?' . http_build_query($params);
        
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Buscar parceiros de negócio (exemplo)
     */
    public function getBusinessPartners(array $filters = [], int $top = 20): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }
        
        $url = rtrim($this->baseUrl, '/') . '/BusinessPartners';
        
        $params = ['$top' => $top];
        if (!empty($filters['filter'])) {
            $params['$filter'] = $filters['filter'];
        }
        if (!empty($filters['select'])) {
            $params['$select'] = $filters['select'];
        }
        
        $url .= '?' . http_build_query($params);
        
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Fazer requisição genérica
     */
    private function makeRequest(string $url, string $method = 'GET', ?array $data = null): array
    {
        try {
            $ch = curl_init($url);
            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Cookie: B1SESSION=' . $this->sessionId . '; ROUTEID=' . $this->routeId
            ];
            
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ];
            
            if ($method === 'POST') {
                $options[CURLOPT_POST] = true;
                if ($data) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data);
                }
            }
            
            curl_setopt_array($ch, $options);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => "HTTP $httpCode"
                ];
            }
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'data' => $result['value'] ?? $result,
                'rows_count' => isset($result['value']) ? count($result['value']) : 1
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout da Service Layer
     */
    public function logout(): void
    {
        if (empty($this->sessionId)) {
            return;
        }
        
        try {
            $url = rtrim($this->baseUrl, '/') . '/Logout';
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Cookie: B1SESSION=' . $this->sessionId . '; ROUTEID=' . $this->routeId
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            curl_exec($ch);
            curl_close($ch);
            
            $this->sessionId = null;
            $this->routeId = null;
            
        } catch (\Exception $e) {
            error_log("Erro ao fazer logout SAP SL: " . $e->getMessage());
        }
    }
    
    /**
     * Destrutor - fazer logout automático
     */
    public function __destruct()
    {
        $this->logout();
    }
}

