<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;

/**
 * SAP Business One Service Layer — cliente **directo** ao endpoint OData (b1s/v1).
 *
 * No desenho actual do administrativo, o fluxo preferido é: PHP → **API gateway** → Service Layer
 * (ver `SapGatewayHttpClient` e a tela «API SAP (integração)»). Mantém-se esta classe para
 * cenários em que o mesmo host exponha o Login SL ou para scripts técnicos pontuais.
 */
class SapB1ServiceLayer
{
    private string $baseUrl = '';

    private string $username = '';

    private string $password = '';

    private string $companyDB = '';

    private ?int $connectionId = null;

    private ?string $sessionId = null;

    private ?string $routeId = null;

    private array $cookies = [];

    public function __construct(?int $connectionId = null)
    {
        $this->connectionId = $connectionId;
        $repo = new AdmsSapServiceLayerConnectionRepository();
        $row = $connectionId !== null && $connectionId > 0
            ? $repo->getById($connectionId)
            : $repo->getDefaultOrFirstActive();

        if (!$row || empty($row['is_active'])) {
            return;
        }

        $this->baseUrl = rtrim((string) ($row['base_url'] ?? ''), '/');
        $this->username = (string) ($row['username'] ?? '');
        $this->password = (string) ($row['password'] ?? '');
        $this->companyDB = (string) ($row['company_db'] ?? '');
        $this->connectionId = (int) ($row['id'] ?? 0) ?: null;

        $baseLower = strtolower($this->baseUrl);
        $looksLikeServiceLayer = str_contains($baseLower, 'b1s');
        if (!$looksLikeServiceLayer) {
            $this->baseUrl = '';
            $this->username = '';
            $this->password = '';
            $this->companyDB = '';
            $this->connectionId = null;
        }
    }

    public function getConnectionId(): ?int
    {
        return $this->connectionId;
    }

    public function hasCredentials(): bool
    {
        return $this->baseUrl !== ''
            && $this->companyDB !== ''
            && $this->username !== ''
            && $this->password !== '';
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
        if (!empty($filters['orderby'])) {
            $params['$orderby'] = (string) $filters['orderby'];
        }

        $url .= '?' . http_build_query($params);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Pesquisa artigos vendáveis (ativos) para preenchimento da grelha — só leitura na UI;
     * no POST da cotação envia-se apenas `ItemCode` (e quantidade; preço opcional).
     *
     * @return array{success: bool, data?: list<array<string, mixed>>, error?: string}
     */
    public function searchSalesItems(string $q, int $top = 40): array
    {
        $top = max(1, min(100, $top));
        $q = trim($q);
        $parts = ["Valid eq 'tYES'"];
        if ($q !== '') {
            $safe = str_replace("'", "''", $q);
            $parts[] = "(startswith(ItemCode,'{$safe}') or contains(ItemCode,'{$safe}') or startswith(ItemName,'{$safe}') or contains(ItemName,'{$safe}'))";
        }
        $filter = implode(' and ', $parts);

        return $this->getItems([
            'filter' => $filter,
            'select' => 'ItemCode,ItemName',
            'orderby' => 'ItemCode',
        ], $top);
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
        if (!empty($filters['orderby'])) {
            $params['$orderby'] = (string) $filters['orderby'];
        }
        
        $url .= '?' . http_build_query($params);
        
        return $this->makeRequest($url, 'GET');
    }

    /**
     * Pesquisa clientes (PN tipo cliente) para seleção de CardCode no portal.
     * Usa `CardType eq 'cCustomer'` e filtro opcional por código ou nome.
     *
     * @return array{success: bool, data?: list<array<string, mixed>>, error?: string}
     */
    public function searchCustomerBusinessPartners(string $q, int $top = 40): array
    {
        $top = max(1, min(100, $top));
        $q = trim($q);
        $parts = ["CardType eq 'cCustomer'"];
        if ($q !== '') {
            $safe = str_replace("'", "''", $q);
            $parts[] = "(startswith(CardCode,'{$safe}') or contains(CardCode,'{$safe}') or startswith(CardName,'{$safe}') or contains(CardName,'{$safe}'))";
        }
        $filter = implode(' and ', $parts);

        return $this->getBusinessPartners([
            'filter' => $filter,
            'select' => 'CardCode,CardName',
            'orderby' => 'CardName',
        ], $top);
    }

    /**
     * Lista cotações de vendas (entidade OData `Quotations`).
     *
     * @param array{card_code?: string, filter?: string, select?: string} $filters
     * @return array{success: bool, data?: list<array<string, mixed>>, rows_count?: int, error?: string}
     */
    public function getQuotations(array $filters = [], int $top = 50): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Quotations';

        $params = [
            '$top' => max(1, min(200, $top)),
            '$orderby' => 'DocEntry desc',
        ];
        if (!empty($filters['select'])) {
            $params['$select'] = $filters['select'];
        } else {
            $params['$select'] = 'DocEntry,DocNum,DocDate,CardCode,CardName,DocTotal,DocCurrency,DocumentStatus';
        }

        $parts = [];
        if (!empty($filters['card_code'])) {
            $card = (string) $filters['card_code'];
            $card = str_replace("'", "''", $card);
            $parts[] = "CardCode eq '{$card}'";
        }
        if (!empty($filters['filter'])) {
            $parts[] = '(' . $filters['filter'] . ')';
        }
        if ($parts !== []) {
            $params['$filter'] = implode(' and ', $parts);
        }

        $url .= '?' . http_build_query($params);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Obtém uma cotação por DocEntry (chave OData em `Quotations`).
     *
     * @return array{success: bool, data?: array<string, mixed>, rows_count?: int, error?: string}
     */
    public function getQuotationByDocEntry(int $docEntry, bool $expandDocumentLines = true): array
    {
        if ($docEntry <= 0) {
            return ['success' => false, 'error' => 'DocEntry inválido.'];
        }
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Quotations(' . $docEntry . ')';
        if ($expandDocumentLines) {
            $url .= '?' . http_build_query(['$expand' => 'DocumentLines']);
        }

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Lista pedidos de venda (entidade OData `Orders`).
     *
     * @param array{card_code?: string, filter?: string, select?: string} $filters
     * @return array{success: bool, data?: list<array<string, mixed>>, rows_count?: int, error?: string}
     */
    public function getOrders(array $filters = [], int $top = 50): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Orders';

        $params = [
            '$top' => max(1, min(200, $top)),
            '$orderby' => 'DocEntry desc',
        ];
        if (!empty($filters['select'])) {
            $params['$select'] = $filters['select'];
        } else {
            $params['$select'] = 'DocEntry,DocNum,DocDate,CardCode,CardName,DocTotal,DocCurrency,DocumentStatus';
        }

        $parts = [];
        if (!empty($filters['card_code'])) {
            $card = (string) $filters['card_code'];
            $card = str_replace("'", "''", $card);
            $parts[] = "CardCode eq '{$card}'";
        }
        if (!empty($filters['filter'])) {
            $parts[] = '(' . $filters['filter'] . ')';
        }
        if ($parts !== []) {
            $params['$filter'] = implode(' and ', $parts);
        }

        $url .= '?' . http_build_query($params);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Obtém um pedido de venda por DocEntry (`Orders`).
     *
     * @return array{success: bool, data?: array<string, mixed>, rows_count?: int, error?: string}
     */
    public function getOrderByDocEntry(int $docEntry, bool $expandDocumentLines = true): array
    {
        if ($docEntry <= 0) {
            return ['success' => false, 'error' => 'DocEntry inválido.'];
        }
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Orders(' . $docEntry . ')';
        if ($expandDocumentLines) {
            $url .= '?' . http_build_query(['$expand' => 'DocumentLines']);
        }

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Lista faturas de cliente (entidade OData `Invoices`).
     *
     * @param array{card_code?: string, filter?: string, select?: string} $filters
     * @return array{success: bool, data?: list<array<string, mixed>>, rows_count?: int, error?: string}
     */
    public function getInvoices(array $filters = [], int $top = 50): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Invoices';

        $params = [
            '$top' => max(1, min(200, $top)),
            '$orderby' => 'DocEntry desc',
        ];
        if (!empty($filters['select'])) {
            $params['$select'] = $filters['select'];
        } else {
            $params['$select'] = 'DocEntry,DocNum,DocDate,CardCode,CardName,DocTotal,DocCurrency,DocumentStatus';
        }

        $parts = [];
        if (!empty($filters['card_code'])) {
            $card = (string) $filters['card_code'];
            $card = str_replace("'", "''", $card);
            $parts[] = "CardCode eq '{$card}'";
        }
        if (!empty($filters['filter'])) {
            $parts[] = '(' . $filters['filter'] . ')';
        }
        if ($parts !== []) {
            $params['$filter'] = implode(' and ', $parts);
        }

        $url .= '?' . http_build_query($params);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Obtém uma fatura de cliente por DocEntry (`Invoices`).
     *
     * @return array{success: bool, data?: array<string, mixed>, rows_count?: int, error?: string}
     */
    public function getInvoiceByDocEntry(int $docEntry, bool $expandDocumentLines = true): array
    {
        if ($docEntry <= 0) {
            return ['success' => false, 'error' => 'DocEntry inválido.'];
        }
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $url = rtrim($this->baseUrl, '/') . '/Invoices(' . $docEntry . ')';
        if ($expandDocumentLines) {
            $url .= '?' . http_build_query(['$expand' => 'DocumentLines']);
        }

        return $this->makeRequest($url, 'GET');
    }

    /**
     * POST JSON num recurso OData (ex.: Orders, Quotations, Invoices).
     * Trata 201 Created e devolve mensagem de erro da Service Layer quando possível.
     *
     * @param string $resourcePath segmento após a base, sem barra inicial (ex.: "Orders")
     * @param array<string, mixed> $body
     * @return array{success: bool, http_code?: int, data?: mixed, error?: string, raw?: string}
     */
    public function postResource(string $resourcePath, array $body): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $path = ltrim($resourcePath, '/');
        $url = rtrim($this->baseUrl, '/') . '/' . $path;

        return $this->performJsonRequest($url, 'POST', $body);
    }

    /**
     * PATCH JSON num recurso OData (ex.: fechar cotação).
     *
     * @param string $resourcePath segmento após a base (ex.: "Quotations(8)")
     * @param array<string, mixed> $body
     * @return array{success: bool, http_code?: int, data?: mixed, error?: string, raw?: string}
     */
    public function patchResource(string $resourcePath, array $body): array
    {
        if (empty($this->sessionId) && !$this->login()) {
            return ['success' => false, 'error' => 'Conexão falhou'];
        }

        $path = ltrim($resourcePath, '/');
        $url = rtrim($this->baseUrl, '/') . '/' . $path;

        return $this->performJsonRequest($url, 'PATCH', $body);
    }

    /**
     * Fazer requisição genérica (GET / POST simples)
     */
    private function makeRequest(string $url, string $method = 'GET', ?array $data = null): array
    {
        $out = $this->performJsonRequest($url, $method, $data);
        if (!$out['success']) {
            return [
                'success' => false,
                'error' => $out['error'] ?? 'Erro na requisição',
            ];
        }

        $result = $out['data'];
        if (!is_array($result)) {
            return [
                'success' => true,
                'data' => $result,
                'rows_count' => 1,
            ];
        }

        return [
            'success' => true,
            'data' => $result['value'] ?? $result,
            'rows_count' => isset($result['value']) && is_array($result['value']) ? count($result['value']) : 1,
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     * @return array{success: bool, http_code?: int, data?: mixed, error?: string, raw?: string}
     */
    private function performJsonRequest(string $url, string $method, ?array $data): array
    {
        try {
            $ch = curl_init($url);

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Cookie: B1SESSION=' . $this->sessionId . '; ROUTEID=' . $this->routeId,
            ];

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];

            $method = strtoupper($method);
            if ($method === 'POST') {
                $options[CURLOPT_POST] = true;
                if ($data !== null) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_THROW_ON_ERROR);
                }
            } elseif ($method !== 'GET') {
                $options[CURLOPT_CUSTOMREQUEST] = $method;
                if ($data !== null) {
                    $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_THROW_ON_ERROR);
                }
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = null;
            if (is_string($response) && $response !== '') {
                try {
                    $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $decoded = null;
                }
            }

            $ok = $httpCode >= 200 && $httpCode < 300;
            if ($ok) {
                return [
                    'success' => true,
                    'http_code' => $httpCode,
                    'data' => $decoded,
                    'raw' => is_string($response) ? $response : '',
                ];
            }

            $err = self::extractSlErrorMessage($decoded) ?? "HTTP $httpCode";

            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => $err,
                'data' => $decoded,
                'raw' => is_string($response) ? $response : '',
            ];
        } catch (\JsonException $e) {
            return [
                'success' => false,
                'error' => 'JSON inválido: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param mixed $decoded
     */
    private static function extractSlErrorMessage($decoded): ?string
    {
        if (!is_array($decoded)) {
            return null;
        }
        if (isset($decoded['error']['message']['value']) && is_string($decoded['error']['message']['value'])) {
            return $decoded['error']['message']['value'];
        }
        if (isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
            return $decoded['error']['message'];
        }

        return null;
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

