# Documentação - Implementação de Captura de Hostname nos Logs

## 📋 Resumo da Implementação

Esta documentação descreve a implementação completa da captura de hostname do equipamento cliente nos logs do sistema administrativo.

**Data de Implementação:** 21/10/2024  
**Versão:** 1.0  
**Status:** ✅ Concluído

---

## 🎯 Objetivos

1. Capturar o hostname do equipamento cliente em todos os logs do sistema
2. Adicionar a coluna `hostname` nas tabelas de logs existentes
3. Atualizar serviços e repositórios para incluir hostname
4. Atualizar views para exibir o hostname
5. Testar performance da captura

---

## 📁 Arquivos Modificados

### 1. **RequestHelper.php**
**Caminho:** `app/adms/Controllers/Services/RequestHelper.php`

**Mudanças:**
- ✅ Adicionado método `getClientHostname()` - Captura hostname via `gethostbyaddr()`
- ✅ Adicionado método `getClientInfo()` - Retorna array com todas as informações do cliente

**Métodos Adicionados:**
```php
public static function getClientHostname(): string
public static function getClientInfo(): array
```

**Funcionalidades:**
- Resolve hostname a partir do IP do cliente
- Retorna 'localhost' para IPs locais (127.0.0.1, ::1)
- Retorna 'N/A' quando hostname não pode ser resolvido
- Usa `@` para suprimir warnings do PHP
- Timeout controlado pela configuração `default_socket_timeout` do PHP

---

### 2. **Migration - AddHostnameToLogs**
**Caminho:** `database/migrations/20251021120000_add_hostname_to_logs.php`

**Mudanças:**
- ✅ Criada migration para adicionar coluna `hostname`
- ✅ Atualiza 4 tabelas de logs

**Tabelas Atualizadas:**
1. `adms_log_alteracoes` - Logs de alterações de dados
2. `adms_log_acessos` - Logs de login/logout
3. `lgpd_logs_lgpd` - Logs LGPD (se existir)
4. `adms_logs` - Logs padrão (se existir)

**Estrutura da Coluna:**
```sql
hostname VARCHAR(255) NULL
COMMENT 'Hostname do equipamento cliente'
```

---

### 3. **LogAlteracaoService.php**
**Caminho:** `app/adms/Models/Services/LogAlteracaoService.php`

**Mudanças:**
- ✅ Adicionado `use App\adms\Controllers\Services\RequestHelper`
- ✅ Captura hostname usando `RequestHelper::getClientHostname()`
- ✅ Inclui hostname no array de dados do log

**Antes:**
```php
'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
```

**Depois:**
```php
$ip = RequestHelper::getClientIp();
$hostname = RequestHelper::getClientHostname();
$userAgent = RequestHelper::getUserAgent();

'ip' => $ip,
'hostname' => $hostname,
'user_agent' => $userAgent,
```

---

### 4. **LogAlteracoesRepository.php**
**Caminho:** `app/adms/Models/Repository/LogAlteracoesRepository.php`

**Mudanças:**
- ✅ Atualizado método `insert()` para incluir campo `hostname`
- ✅ Adicionado binding do parâmetro `:hostname`

**SQL Atualizado:**
```sql
INSERT INTO adms_log_alteracoes 
(tabela, objeto_id, usuario_id, data_alteracao, tipo_operacao, ip, hostname, user_agent, criado_por) 
VALUES 
(:tabela, :objeto_id, :usuario_id, :data_alteracao, :tipo_operacao, :ip, :hostname, :user_agent, :criado_por)
```

---

### 5. **LogAcessosRepository.php**
**Caminho:** `app/adms/Models/Repository/LogAcessosRepository.php`

**Mudanças:**
- ✅ Atualizado método `insert()` para incluir campo `hostname`
- ✅ Atualizado método `registrarAcesso()` para aceitar parâmetro `$hostname`

**Assinatura Atualizada:**
```php
public function registrarAcesso(
    int $usuarioId, 
    string $tipoAcesso, 
    string $ip, 
    ?string $userAgent = null, 
    ?string $detalhes = null, 
    ?string $hostname = null
): bool
```

---

### 6. **Login.php**
**Caminho:** `app/adms/Controllers/login/Login.php`

**Mudanças:**
- ✅ Captura hostname em 3 locais diferentes:
  1. Login normal
  2. Force password change
  3. Logout concurrent

**Código Adicionado:**
```php
$hostname = RequestHelper::getClientHostname();
$logAcessosRepo->registrarAcesso(
    (int)$result['id'], 
    'LOGIN', 
    $ip, 
    $userAgent, 
    null, 
    $hostname
);
```

---

### 7. **Logout.php**
**Caminho:** `app/adms/Controllers/login/Logout.php`

**Mudanças:**
- ✅ Captura hostname durante logout

**Código Adicionado:**
```php
$hostname = RequestHelper::getClientHostname();
$logAcessosRepo->registrarAcesso(
    (int)$_SESSION['user_id'], 
    'LOGOUT', 
    $ip, 
    $ua, 
    null, 
    $hostname
);
```

---

### 8. **listLogAlteracoes.php**
**Caminho:** `app/adms/Views/logs/listLogAlteracoes.php`

**Mudanças:**
- ✅ Adicionada coluna "Hostname" na tabela desktop
- ✅ Adicionado campo "Hostname" nos cards mobile
- ✅ Ajustadas larguras das colunas para acomodar novo campo
- ✅ Atualizado colspan de 10 para 11

**Desktop:**
```html
<th class="text-start" style="width: 12%; padding-left: 8px;">Hostname</th>
```

**Mobile:**
```html
<div class="col-12">
    <div class="d-flex align-items-center mb-2">
        <i class="fas fa-desktop text-info me-2"></i>
        <span class="fw-semibold text-info">Hostname:</span>
    </div>
    <div class="ms-4">
        <span class="badge bg-info text-white"><?= htmlspecialchars($log['hostname'] ?? 'N/A') ?></span>
    </div>
</div>
```

---

### 9. **listLogAcessos.php**
**Caminho:** `app/adms/Views/logs/listLogAcessos.php`

**Mudanças:**
- ✅ Adicionada coluna "Hostname" na tabela desktop
- ✅ Adicionado campo "Hostname" nos cards mobile
- ✅ Ajustadas larguras das colunas

**Ícone Utilizado:**
```html
<i class="fas fa-server text-primary me-1"></i>
```

---

## 🔧 Como Funciona

### Fluxo de Captura de Hostname

```
1. Usuário acessa o sistema
   ↓
2. RequestHelper::getClientIp() → Captura IP
   ↓
3. RequestHelper::getClientHostname() → Resolve hostname via gethostbyaddr()
   ↓
4. Log é registrado com IP + Hostname
   ↓
5. Informação é exibida nas views de logs
```

### Tratamento de Casos Especiais

| Cenário | Resultado |
|---------|-----------|
| IP = 127.0.0.1 ou ::1 | `localhost` |
| IP inválido ou 0.0.0.0 | `localhost` |
| Hostname não resolvido | `N/A` |
| Erro na resolução | `N/A` |
| Hostname resolvido | Nome do host |

---

## ⚡ Performance

### Configuração Recomendada (php.ini)

```ini
default_socket_timeout = 2  ; Timeout de 2 segundos para DNS
```

### Métricas Esperadas

- **IPs Locais (127.0.0.1):** < 1ms
- **IPs de Rede Interna:** 10-50ms
- **IPs Externos com DNS:** 50-500ms
- **IPs sem hostname:** 50-2000ms (timeout)

### Script de Teste

Execute o script de teste para avaliar a performance:

```bash
php test_hostname_performance.php
```

**Saída Esperada:**
- Tempo médio de execução
- Status de performance (Excelente/Aceitável/Ruim)
- Recomendações específicas

---

## 📊 Estrutura das Tabelas

### adms_log_alteracoes

```sql
id                INT PRIMARY KEY AUTO_INCREMENT
tabela            VARCHAR(100)
objeto_id         INT
usuario_id        INT
data_alteracao    DATETIME
tipo_operacao     ENUM('INSERT', 'UPDATE', 'DELETE')
ip                VARCHAR(45)
hostname          VARCHAR(255)  ← NOVO
user_agent        VARCHAR(255)
criado_por        INT
```

### adms_log_acessos

```sql
id                INT PRIMARY KEY AUTO_INCREMENT
usuario_id        INT
tipo_acesso       VARCHAR(50)
ip                VARCHAR(45)
hostname          VARCHAR(255)  ← NOVO
user_agent        VARCHAR(255)
data_acesso       DATETIME
detalhes          TEXT
criado_por        INT
```

---

## 🧪 Como Testar

### 1. Testar Captura de Hostname

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\adms\Controllers\Services\RequestHelper;

// Testar com IP atual
$ip = RequestHelper::getClientIp();
$hostname = RequestHelper::getClientHostname();

echo "IP: {$ip}\n";
echo "Hostname: {$hostname}\n";

// Testar com informações completas
$info = RequestHelper::getClientInfo();
print_r($info);
```

### 2. Testar Login/Logout

1. Faça login no sistema
2. Acesse: `list-log-acessos`
3. Verifique se a coluna "Hostname" está preenchida
4. Faça logout
5. Verifique novamente os logs

### 3. Testar Logs de Alteração

1. Crie/Edite/Exclua um registro (ex: usuário, departamento)
2. Acesse: `list-log-alteracoes`
3. Verifique se a coluna "Hostname" está preenchida

---

## 🚀 Próximos Passos (Fase 2)

Conforme o planejamento inicial, as próximas fases incluem:

### Fase 2 - Implementação de Logs nos Módulos

1. **Alta Prioridade:**
   - [ ] Departamentos
   - [ ] Bancos
   - [ ] Centros de Custo
   - [ ] LGPD (crítico - 95 arquivos)

2. **Média Prioridade:**
   - [ ] Estoque/Inventário (31 arquivos)
   - [ ] Treinamentos (completar)
   - [ ] Informativos
   - [ ] Documentos

3. **Baixa Prioridade:**
   - [ ] Configurações
   - [ ] Notificações

---

## 💡 Otimizações Futuras (Opcional)

### 1. Implementar Cache de Hostnames

```php
public static function getClientHostname(): string
{
    $ip = self::getClientIp();
    
    // Verificar cache
    $cacheKey = 'hostname_' . md5($ip);
    $cached = apcu_fetch($cacheKey);
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Resolver hostname
    $hostname = @gethostbyaddr($ip);
    
    // Armazenar em cache por 1 hora
    apcu_store($cacheKey, $hostname, 3600);
    
    return $hostname;
}
```

### 2. Resolução Assíncrona (Background)

Para sistemas com alto volume de acessos, considerar resolver hostname em background usando filas (ex: Redis Queue, RabbitMQ).

### 3. Fallback para APIs Externas

```php
// Se gethostbyaddr falhar, tentar API externa
if ($hostname === $ip) {
    $hostname = file_get_contents("https://api.ipify.org?hostname={$ip}");
}
```

---

## 📝 Notas Importantes

### Segurança

⚠️ **IMPORTANTE:** Hostnames podem ser falsificados ou manipulados. Use-os apenas como informação adicional, nunca como identificador principal de segurança.

### Privacidade

ℹ️ De acordo com a LGPD, hostnames podem ser considerados dados pessoais em alguns contextos. Certifique-se de:
- Informar usuários sobre coleta de hostname na Política de Privacidade
- Implementar retenção adequada de logs
- Permitir que usuários solicitem remoção de dados

### Compatibilidade

✅ Compatível com:
- PHP 7.4+
- MySQL 5.7+
- Todos os navegadores modernos

---

## 🆘 Troubleshooting

### Problema: Hostname sempre retorna 'N/A'

**Possíveis Causas:**
1. Servidor DNS não configurado
2. Firewall bloqueando consultas DNS reversas
3. IP não tem entrada PTR no DNS

**Solução:**
```bash
# Testar resolução DNS manualmente
nslookup <IP>
dig -x <IP>
```

### Problema: Performance ruim (> 2s)

**Solução:**
1. Reduzir `default_socket_timeout` no php.ini
2. Implementar cache de hostnames
3. Considerar execução assíncrona

### Problema: Coluna hostname não aparece nas views

**Solução:**
1. Verificar se a migration foi executada:
   ```bash
   php vendor/bin/phinx status
   ```
2. Verificar se as views foram atualizadas
3. Limpar cache do navegador (Ctrl + F5)

---

## 📞 Suporte

Para dúvidas ou problemas:
- Consulte este documento
- Execute `test_hostname_performance.php`
- Verifique os logs em `app/logs/`

---

## ✅ Checklist de Implementação

- [x] RequestHelper atualizado com métodos de hostname
- [x] Migration criada e executada
- [x] LogAlteracaoService atualizado
- [x] LogAlteracoesRepository atualizado
- [x] LogAcessosRepository atualizado
- [x] Login.php atualizado
- [x] Logout.php atualizado
- [x] View listLogAlteracoes.php atualizada
- [x] View listLogAcessos.php atualizada
- [x] Script de teste criado
- [x] Documentação completa

**Status Final:** ✅ **FASE 1 CONCLUÍDA COM SUCESSO**

---

*Documento criado em: 21/10/2024*  
*Última atualização: 21/10/2024*

