# 👨‍💻 Tutorial Prático: API Gateway em 30 Minutos

## 🎯 O Que Vamos Fazer

Criar um **API Gateway funcional** que:
- ✅ Roda em **1 servidor na empresa**
- ✅ Conecta ao **SAP B1 HANA local**
- ✅ Expõe **APIs REST seguras** na internet
- ✅ Sua **aplicação web** consome essas APIs

**Tempo estimado:** 30 minutos

---

## 📋 Checklist Inicial

Você vai precisar de:
```
[ ] 1 servidor/VM na empresa (Windows ou Linux)
[ ] PHP 8.1+ instalado
[ ] SAP HANA Client (ODBC) instalado
[ ] Acesso SSH ao servidor
[ ] Permissão para abrir porta 443 no firewall
[ ] 30 minutos livres
```

---

## 🚀 PASSO 1: Preparar Servidor (5 min)

### **No Servidor da Empresa:**

```bash
# Linux (Ubuntu/Debian)
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-mbstring php8.1-xml php8.1-odbc
sudo apt install composer nginx certbot

# Windows Server
choco install php composer nginx
```

**Verificar:**
```bash
php -v                    # PHP 8.1+
composer --version        # Composer 2.x
php -m | grep pdo_odbc    # PDO ODBC instalado
```

---

## 🚀 PASSO 2: Gerar Projeto (2 min)

```bash
# Criar diretório
sudo mkdir -p /opt/sap-api-gateway
sudo chown $USER:$USER /opt/sap-api-gateway
cd /opt/sap-api-gateway

# Usar script gerador automático
php /caminho/para/scripts/generate_api_gateway.php /opt/sap-api-gateway

# Instalar dependências
composer install
```

**Resultado:**
```
✅ Estrutura criada
✅ Composer.json configurado
✅ Dependências instaladas
```

---

## 🚀 PASSO 3: Configurar Conexão SAP (3 min)

### **3.1 - Copiar e editar `.env`:**

```bash
cp .env.example .env
nano .env
```

### **3.2 - Preencher credenciais:**

```env
# SAP HANA (ATENÇÃO: usar IP local e usuário read-only!)
SAP_HANA_DSN=SBO_TIARAJU_HOM
SAP_HANA_USER=APP_READONLY
SAP_HANA_PASSWORD=SenhaForte123!
SAP_HANA_SCHEMA=SBO_TIARAJU_HOM

# Segurança
JWT_SECRET=abc123def456ghi789jkl012mno345pqr  # ⚠️ Gere uma chave forte!
ALLOWED_ORIGIN=https://seu-dominio-hospedagem.com.br

# Rate Limiting
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

### **3.3 - Criar usuário read-only no SAP:**

```sql
-- Conecte ao SAP HANA Studio e execute:
CREATE USER APP_READONLY PASSWORD "SenhaForte123!";
GRANT SELECT ON SCHEMA "SBO_TIARAJU_HOM" TO APP_READONLY;
REVOKE INSERT, UPDATE, DELETE ON SCHEMA "SBO_TIARAJU_HOM" FROM APP_READONLY;
```

---

## 🚀 PASSO 4: Implementar Controllers (10 min)

### **4.1 - Database.php:**

```bash
nano src/Config/Database.php
```

**Cole o código completo do guia** (`GUIA_API_GATEWAY_SAP_B1.md` - seção 3.2)

### **4.2 - HealthController.php:**

```bash
nano src/Controllers/HealthController.php
```

**Cole o código completo do guia** (seção 3.6)

### **4.3 - SalesController.php:**

```bash
nano src/Controllers/SalesController.php
```

**Cole o código completo do guia** (seção 3.5)

### **4.4 - Atualizar public/index.php:**

```bash
nano public/index.php
```

**Substitua pelo código completo do guia** (seção 3.1)

---

## 🚀 PASSO 5: Testar Localmente (3 min)

```bash
# Iniciar servidor de teste
php -S localhost:8080 -t public

# Em outro terminal, testar:
curl http://localhost:8080/health
```

**Resposta esperada:**
```json
{
  "status": "ok",
  "timestamp": "2025-11-04 12:00:00",
  "checks": {
    "sap_hana": "ok"
  }
}
```

**Se funcionar:** ✅ API Gateway local OK!

---

## 🚀 PASSO 6: Configurar Nginx (5 min)

### **6.1 - Criar config:**

```bash
sudo nano /etc/nginx/sites-available/sap-api
```

**Cole:**
```nginx
server {
    listen 80;
    server_name api-sap.empresa.local;
    root /opt/sap-api-gateway/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### **6.2 - Ativar site:**

```bash
sudo ln -s /etc/nginx/sites-available/sap-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### **6.3 - Testar:**

```bash
curl http://localhost/health
```

---

## 🚀 PASSO 7: Configurar SSL (2 min)

```bash
# Obter certificado SSL
sudo certbot --nginx -d api-sap.empresa.com.br

# Certbot configura automaticamente!
```

**Testar:**
```bash
curl https://api-sap.empresa.com.br/health
```

---

## 🚀 PASSO 8: Configurar Firewall (2 min)

```bash
# Apenas HTTPS
sudo ufw allow 443/tcp

# Apenas do IP da hospedagem
sudo ufw allow from IP_HOSPEDAGEM to any port 443 proto tcp

# Bloquear tudo mais
sudo ufw enable
```

---

## 🚀 PASSO 9: Integrar App Web (3 min)

### **9.1 - No servidor de hospedagem, editar `.env`:**

```env
# Ao invés de conectar direto ao HANA:
# SAP_HANA_HOST=192.168.X.X  ❌

# Usar API Gateway:
SAP_API_GATEWAY_URL=https://api-sap.empresa.com.br
SAP_API_USER=aplicacao_web
SAP_API_PASSWORD=senha_forte_api
SAP_API_MODE=gateway  # ← Flag para usar gateway
```

### **9.2 - Criar service na aplicação:**

Já preparei! Use: `app/adms/Models/Services/SapApiGatewayService.php`

### **9.3 - Modificar DynamicQueryBuilderService:**

```php
// Adicionar no início do executeCustomSQL():

if ($_ENV['SAP_API_MODE'] === 'gateway') {
    return $this->executeViaSapApiGateway($sql);
}

// Continua com conexão direta...
```

---

## ✅ PASSO 10: Testar Tudo (2 min)

### **10.1 - Testar API Gateway:**

```bash
# Health check
curl https://api-sap.empresa.com.br/health

# Total de vendas (requer autenticação)
curl https://api-sap.empresa.com.br/api/sales/total \
  -H "Authorization: Bearer SEU_TOKEN"
```

### **10.2 - Testar na aplicação web:**

Acesse:
```
https://seu-dominio.com.br/administrativo/view-dynamic-report?id=13
```

**Se aparecer o gráfico de vendedores:** ✅ **FUNCIONOU!**

---

## 📊 Exemplo Completo: Endpoint de Vendas

### **Na API Gateway:**

```php
// src/Controllers/SalesController.php
public function getBySeller(Request $request, Response $response): Response
{
    $conn = Database::getConnection();
    
    $sql = 'SELECT 
                s."SlpName" AS "Vendedor",
                TO_DECIMAL(SUM(h."DocTotal"), 15, 2) AS "Total"
            FROM "OINV" h
            INNER JOIN "OSLP" s ON s."SlpCode" = h."SlpCode"
            WHERE h."CANCELED" = \'N\'
            GROUP BY s."SlpName"
            ORDER BY SUM(h."DocTotal") DESC';
    
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll();
    
    // Converter UTF-8
    array_walk_recursive($results, function(&$item) {
        if (is_string($item)) {
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
        }
    });
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'data' => $results
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
}
```

### **Na Aplicação Web:**

```php
// app/adms/Models/Services/SapApiGatewayService.php
public function getSalesBySeller(): array
{
    return $this->request('GET', '/api/sales/by-seller');
}

// Usar:
$gateway = new SapApiGatewayService();
$vendedores = $gateway->getSalesBySeller();
// Retorna: ['success' => true, 'data' => [...]]
```

---

## 🔍 Troubleshooting

### **Problema: "Connection refused"**
```bash
# Verificar se Nginx está rodando
sudo systemctl status nginx

# Verificar logs
sudo tail -f /var/log/nginx/error.log
```

### **Problema: "Could not find driver"**
```bash
# Verificar ODBC
php -m | grep odbc

# Configurar DSN
sudo nano /etc/odbc.ini
```

### **Problema: "Token inválido"**
```bash
# Verificar JWT_SECRET
# Deve ser o MESMO na API e na aplicação
```

---

## 📈 Performance

### **Cache de Queries:**

```php
// Adicionar em SalesController
private function getCached(string $key, callable $callback, int $ttl = 300)
{
    $file = "/opt/sap-api-gateway/cache/{$key}.json";
    
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        return json_decode(file_get_contents($file), true);
    }
    
    $data = $callback();
    file_put_contents($file, json_encode($data));
    
    return $data;
}

// Usar:
public function getTopProducts(Request $request, Response $response): Response
{
    $data = $this->getCached('top_products', function() {
        // Query aqui...
        return $results;
    }, 300); // Cache 5 minutos
    
    // Retornar...
}
```

---

## 📊 Monitoramento

### **Log de Requisições:**

```php
// Adicionar em cada controller
error_log(sprintf(
    "[API] %s %s - IP: %s - User: %s - Time: %.4fs",
    $request->getMethod(),
    $request->getUri()->getPath(),
    $request->getServerParams()['REMOTE_ADDR'],
    $user->username ?? 'anonymous',
    microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
));
```

### **Analisar Logs:**

```bash
# Ver últimas requisições
tail -n 50 /opt/sap-api-gateway/logs/api.log

# Contar requisições por endpoint
cat /opt/sap-api-gateway/logs/api.log | grep -oP '/api/\S+' | sort | uniq -c | sort -rn

# Ver erros
grep ERROR /opt/sap-api-gateway/logs/api.log
```

---

## 🎓 Próximos Passos

### **1. Adicionar Mais Endpoints**
- Clientes
- Pedidos
- Estoque
- Notas fiscais

### **2. Implementar Cache Inteligente**
- Redis para cache distribuído
- Invalidação automática

### **3. Melhorar Autenticação**
- OAuth 2.0
- Refresh tokens
- Múltiplos usuários

### **4. Documentar APIs**
- Swagger/OpenAPI
- Postman Collection
- Exemplos de uso

### **5. Monitoramento Avançado**
- Grafana + Prometheus
- Alertas de erro
- Dashboard de performance

---

## 📚 Resumo dos Arquivos

| Arquivo | Descrição | Status |
|---------|-----------|--------|
| `GUIA_API_GATEWAY_SAP_B1.md` | Guia completo detalhado | ✅ Criado |
| `RESUMO_API_GATEWAY.md` | Resumo executivo | ✅ Criado |
| `TUTORIAL_PRATICO_API_GATEWAY.md` | Este tutorial | ✅ Criado |
| `scripts/generate_api_gateway.php` | Gerador automático | ✅ Criado |
| `config/env.production.example` | Exemplo .env | ✅ Criado |
| `scripts/check_sap_connection.php` | Teste conexão | ✅ Criado |

---

## 🎉 Conclusão

**Em 30 minutos você terá:**
- ✅ API Gateway rodando na empresa
- ✅ SAP B1 protegido (nunca exposto)
- ✅ APIs REST seguras e rápidas
- ✅ Aplicação web conectada
- ✅ Monitoramento e logs
- ✅ Cache para performance
- ✅ Rate limiting
- ✅ Autenticação JWT

---

## 🆘 Precisa de Ajuda?

**Consulte:**
1. `GUIA_API_GATEWAY_SAP_B1.md` - Detalhes técnicos
2. `RESUMO_API_GATEWAY.md` - Visão geral
3. `DEPLOY_PRODUCAO_SAP_B1.md` - Comparação com VPN

---

**🚀 Mãos à obra! Em 30 minutos você terá um sistema profissional!**

