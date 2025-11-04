# 🚀 Guia de Deploy para Produção - SAP B1 em Rede Local

## 📋 Cenário

- **Aplicação Web:** Em servidor de hospedagem (externo)
- **Banco SAP B1 HANA:** Em servidor local (rede interna da empresa)
- **Desafio:** Conectar a aplicação externa ao banco interno com segurança

---

## 🎯 Opções de Conectividade

### **OPÇÃO 1: VPN (RECOMENDADO) ⭐**

Criar uma VPN entre o servidor de hospedagem e a rede da empresa.

#### **Vantagens:**
✅ **Mais seguro** - Túnel criptografado  
✅ **Transparente** - Aplicação acessa como se estivesse na rede local  
✅ **Flexível** - Pode acessar outros recursos da rede  
✅ **Padrão da indústria** - Solução profissional

#### **Desvantagens:**
❌ Requer configuração no servidor e no firewall da empresa  
❌ Pode ter custo adicional na hospedagem

#### **Como Implementar:**

1. **No Servidor da Empresa:**
   - Instalar servidor VPN (OpenVPN, WireGuard, IPSec)
   - Configurar firewall para liberar porta VPN
   - Criar usuário/certificado para o servidor web

2. **No Servidor de Hospedagem:**
   - Instalar cliente VPN
   - Conectar à VPN da empresa
   - Configurar rota para o SAP HANA

3. **No `.env` (Hospedagem):**
```env
# IP interno do SAP HANA (via VPN)
SAP_HANA_HOST=192.168.X.X
SAP_HANA_PORT=30015
SAP_HANA_USER=seu_usuario
SAP_HANA_PASSWORD=sua_senha
SAP_HANA_SCHEMA=SBO_TIARAJU_HOM
```

---

### **OPÇÃO 2: Túnel SSH (Alternativa Simples)**

Usar SSH para criar um túnel entre os servidores.

#### **Vantagens:**
✅ **Simples** - Fácil de configurar  
✅ **Sem custo adicional** - Usa SSH nativo  
✅ **Criptografado** - Seguro

#### **Desvantagens:**
❌ Menos estável que VPN  
❌ Requer manutenção do túnel  
❌ Pode cair e precisar reconexão

#### **Como Implementar:**

1. **No Servidor da Empresa:**
   - Habilitar SSH externo (ou usar máquina jump)
   - Liberar porta SSH no firewall

2. **No Servidor de Hospedagem:**
   - Criar túnel SSH:
```bash
ssh -L 30015:192.168.X.X:30015 usuario@ip-publico-empresa -N -f
```

3. **No `.env` (Hospedagem):**
```env
# Localhost através do túnel SSH
SAP_HANA_HOST=127.0.0.1
SAP_HANA_PORT=30015
SAP_HANA_USER=seu_usuario
SAP_HANA_PASSWORD=sua_senha
SAP_HANA_SCHEMA=SBO_TIARAJU_HOM
```

---

### **OPÇÃO 3: IP Público + Firewall (NÃO RECOMENDADO)**

Expor o SAP HANA na internet com IP público.

#### **Vantagens:**
✅ **Simples** - Conexão direta  
✅ **Sem túnel** - Menos configuração

#### **Desvantagens:**
❌ **MUITO INSEGURO** - SAP HANA exposto na internet  
❌ **Risco alto** - Alvo de ataques  
❌ **Não recomendado pela SAP**

#### **⚠️ SE FOR USAR (não recomendado):**

1. **Firewall Restritivo:**
   - Liberar **APENAS** o IP do servidor de hospedagem
   - Usar porta não-padrão
   - Implementar fail2ban

2. **No `.env` (Hospedagem):**
```env
SAP_HANA_HOST=IP-PUBLICO-EMPRESA
SAP_HANA_PORT=30015
SAP_HANA_USER=seu_usuario
SAP_HANA_PASSWORD=sua_senha_forte
SAP_HANA_SCHEMA=SBO_TIARAJU_HOM
```

---

### **OPÇÃO 4: API Gateway / Middleware (Profissional)**

Criar uma camada intermediária na empresa que expõe apenas APIs.

#### **Vantagens:**
✅ **Muito seguro** - SAP HANA nunca exposto  
✅ **Controle total** - API customizada  
✅ **Performance** - Cache, rate limiting  
✅ **Auditoria** - Log de todas as chamadas

#### **Desvantagens:**
❌ Complexo - Requer desenvolvimento  
❌ Manutenção - Mais uma camada

#### **Arquitetura:**
```
[App Web Externa] 
    ↓ HTTPS
[API Gateway na Empresa] 
    ↓ Local
[SAP B1 HANA]
```

---

## ⚙️ Configurações do Sistema

### **1. Arquivo `.env` (Produção)**

Crie um arquivo `.env.production` separado:

```env
# ============================================
# CONFIGURAÇÕES DE PRODUÇÃO
# ============================================

# DATABASE PRINCIPAL (MySQL)
DB_HOST=host-mysql-hospedagem.com
DB_NAME=nome_banco_producao
DB_USER=usuario_producao
DB_PASS=senha_forte_producao

# SAP B1 HANA (via VPN/Túnel)
SAP_HANA_HOST=192.168.X.X  # ou 127.0.0.1 se túnel SSH
SAP_HANA_PORT=30015
SAP_HANA_USER=usuario_readonly  # ⚠️ USAR USUÁRIO READ-ONLY!
SAP_HANA_PASSWORD=senha_forte_aqui
SAP_HANA_SCHEMA=SBO_PRODUCAO

# URLs
URL_ADM=https://seu-dominio.com.br/administrativo/

# AMBIENTE
APP_ENV=production
APP_DEBUG=false
```

### **2. Usuário SAP HANA Read-Only (IMPORTANTE)**

**⚠️ NUNCA use usuário com permissões de escrita!**

```sql
-- Criar usuário read-only no SAP HANA
CREATE USER APP_READONLY PASSWORD "SenhaForte123!";

-- Conceder apenas SELECT no schema
GRANT SELECT ON SCHEMA "SBO_PRODUCAO" TO APP_READONLY;

-- Negar INSERT, UPDATE, DELETE
REVOKE INSERT, UPDATE, DELETE ON SCHEMA "SBO_PRODUCAO" FROM APP_READONLY;
```

### **3. Arquivo `config.php` ou Similar**

Detectar ambiente automaticamente:

```php
<?php
// config/environment.php

// Detectar ambiente
$environment = getenv('APP_ENV') ?: 'development';

// Carregar .env correto
if ($environment === 'production') {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../', '.env.production');
} else {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
}

$dotenv->load();
```

---

## 🔒 Checklist de Segurança

### **Antes do Deploy:**

- [ ] **Usuário Read-Only** - Criar usuário SAP com permissões mínimas
- [ ] **Senhas Fortes** - Senhas com 16+ caracteres, letras, números, símbolos
- [ ] **VPN Configurada** - Ou túnel SSH ativo e monitorado
- [ ] **Firewall Restritivo** - Apenas IPs autorizados
- [ ] **SSL/TLS** - Aplicação HTTPS obrigatório
- [ ] **Debug Desligado** - `APP_DEBUG=false` em produção
- [ ] **Logs Protegidos** - Diretório `logs/` não acessível via web
- [ ] **`.env` Protegido** - Nunca commitar no Git
- [ ] **Backup** - Estratégia de backup configurada
- [ ] **Monitoramento** - Alertas de conectividade

### **Após o Deploy:**

- [ ] **Testar Conexão** - Verificar se conecta ao SAP HANA
- [ ] **Testar Queries** - Executar relatórios de teste
- [ ] **Verificar Logs** - Checar se não há erros
- [ ] **Monitorar Performance** - Tempo de resposta aceitável
- [ ] **Documentar** - IPs, portas, credenciais (em local seguro)

---

## 🛠️ Configurações Adicionais

### **1. Timeout de Conexão**

Em `app/adms/Models/Services/SapB1HanaConnection.php`:

```php
private function connect(): PDO
{
    try {
        $dsn = "odbc:{$this->config['dsn']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,  // ⬅️ Timeout de 30 segundos
            PDO::ATTR_PERSISTENT => false  // ⬅️ Não usar conexões persistentes
        ];
        
        $this->connection = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
        
        // Configurar schema
        if (!empty($this->config['schema'])) {
            $this->connection->exec("SET SCHEMA \"{$this->config['schema']}\"");
        }
        
        return $this->connection;
        
    } catch (PDOException $e) {
        error_log("Erro na conexão SAP HANA: " . $e->getMessage());
        throw new Exception("Não foi possível conectar ao SAP B1 HANA");
    }
}
```

### **2. Cache de Queries (Opcional)**

Para reduzir carga no SAP HANA:

```php
// Em app/adms/Models/Services/DynamicQueryBuilderService.php

private function getCachedResult(string $sql): ?array
{
    $cacheKey = 'query_' . md5($sql);
    $cacheFile = __DIR__ . '/../../../cache/' . $cacheKey . '.json';
    
    // Cache de 5 minutos
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    return null;
}

private function setCachedResult(string $sql, array $result): void
{
    $cacheKey = 'query_' . md5($sql);
    $cacheFile = __DIR__ . '/../../../cache/' . $cacheKey . '.json';
    
    @mkdir(dirname($cacheFile), 0755, true);
    file_put_contents($cacheFile, json_encode($result));
}
```

### **3. Monitoramento de Conectividade**

Script de health check:

```php
<?php
// public/health-check.php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Testar MySQL
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $health['checks']['mysql'] = 'ok';
} catch (Exception $e) {
    $health['checks']['mysql'] = 'error';
    $health['status'] = 'error';
}

// Testar SAP HANA
try {
    $sapConn = \App\adms\Models\Services\SapB1HanaConnection::getInstance();
    $sapConn->query("SELECT 1 FROM DUMMY");
    $health['checks']['sap_hana'] = 'ok';
} catch (Exception $e) {
    $health['checks']['sap_hana'] = 'error';
    $health['status'] = 'error';
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health);
```

---

## 📊 Comparação de Opções

| Critério | VPN | SSH Túnel | IP Público | API Gateway |
|----------|-----|-----------|------------|-------------|
| **Segurança** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Performance** | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Facilidade** | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Estabilidade** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Custo** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ |
| **Recomendado** | ✅ SIM | ✅ OK | ❌ NÃO | ✅ IDEAL |

---

## 🎯 Recomendação Final

### **Para Pequena/Média Empresa:**
**VPN (OpenVPN ou WireGuard)**
- Seguro, estável e profissional
- Custo baixo a médio
- Fácil de manter

### **Para Teste Rápido:**
**Túnel SSH**
- Rápido para implementar
- Sem custo adicional
- Funciona bem para POC

### **Para Grande Empresa:**
**API Gateway**
- Máxima segurança
- Controle total
- Escalável

### **NUNCA:**
**IP Público Direto**
- Muito arriscado
- Contra boas práticas
- Violação de compliance

---

## 📞 Suporte

Em caso de dúvidas durante o deploy:
1. Consulte a documentação do SAP HANA
2. Consulte seu provedor de hospedagem
3. Considere contratar consultoria de infraestrutura

---

## ✅ Checklist Final de Deploy

```
[ ] VPN ou túnel configurado e testado
[ ] .env.production criado e configurado
[ ] Usuário read-only criado no SAP HANA
[ ] Firewall configurado (whitelist IPs)
[ ] SSL/HTTPS ativo na aplicação
[ ] Debug mode desligado (APP_DEBUG=false)
[ ] Logs protegidos e monitorados
[ ] Backup configurado
[ ] Health check implementado
[ ] Documentação atualizada
[ ] Equipe treinada
[ ] Plano de rollback definido
```

---

**🚀 Boa sorte no deploy!**

