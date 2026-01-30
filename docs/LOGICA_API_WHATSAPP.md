# Lógica da API WhatsApp - Explicação Completa

## 📋 Visão Geral

O sistema possui uma integração flexível com WhatsApp que suporta **3 provedores diferentes**:
1. **Evolution API** (Open Source - Recomendado)
2. **Twilio** (Pago - Internacional)
3. **Meta/Facebook** (Oficial - Requer aprovação)

---

## 🏗️ Arquitetura

### Componentes Principais

```
┌─────────────────────────────────────────────────────────────┐
│                    Sistema Administrativo                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │   SendWhatsAppService (Helper)        │
        │   - sendMessage()                     │
        │   - sendViaEvolution()                │
        │   - sendViaTwilio()                    │
        │   - sendViaMeta()                      │
        └───────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │   AdmsWhatsAppConfigRepository         │
        │   - getConfig()                        │
        │   - saveConfig()                       │
        └───────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │   Tabela: adms_whatsapp_config         │
        │   - Armazena configurações             │
        └───────────────────────────────────────┘
                            │
                            ▼
        ┌───────────────────────────────────────┐
        │   API Externa (Evolution/Twilio/Meta)  │
        └───────────────────────────────────────┘
```

---

## 🔄 Fluxo de Funcionamento

### 1. Configuração Inicial

**Arquivo:** `app/adms/Controllers/settings/WhatsAppConfig.php`

**Processo:**
1. Usuário acessa `/whats-app-config`
2. Sistema busca configuração ativa no banco (`AdmsWhatsAppConfigRepository::getConfig()`)
3. Exibe formulário com campos:
   - **Provedor** (Evolution/Twilio/Meta)
   - **URL da API**
   - **API Key**
   - **API Token** (opcional)
   - **Nome da Instância** (Evolution)
   - **Número WhatsApp** (Twilio)
   - **Webhook URL** (opcional)
   - **Ativar/Desativar**

**Salvamento:**
- Controller: `SaveWhatsAppConfig.php`
- Repository: `AdmsWhatsAppConfigRepository::saveConfig()`
- Tabela: `adms_whatsapp_config`
- **Lógica:** Se já existe configuração, faz UPDATE. Se não existe, faz INSERT.

---

### 2. Envio de Mensagem

**Arquivo:** `app/adms/Helpers/SendWhatsAppService.php`

**Método Principal:** `sendMessage($phoneNumber, $message, $options = [])`

#### Passo 1: Buscar Configuração
```php
$repo = new AdmsWhatsAppConfigRepository();
$config = $repo->getConfig();

// Verificar se está ativo
if (empty($config) || !$config['is_active']) {
    return ['success' => false, 'error' => 'WhatsApp não configurado ou desativado'];
}
```

#### Passo 2: Formatar Número de Telefone
```php
// Limpar (apenas dígitos)
$phoneNumber = preg_replace('/\D/', '', $phoneNumber);

// Garantir DDI brasileiro (55)
// Se tem 10 ou 11 dígitos → adicionar 55
if (strlen($phoneNumber) === 10 || strlen($phoneNumber) === 11) {
    $phoneNumber = '55' . $phoneNumber;
}

// Resultado: 5541999887766 (DDI + DDD + número)
```

**Lógica:**
- Número local (10-11 dígitos): `41999887766` → `5541999887766`
- Número com DDI incorreto: `41999887766` → `5541999887766`
- Número já completo: `5541999887766` → mantém

#### Passo 3: Escolher Provedor
```php
return match ($config['api_provider']) {
    'Evolution' => self::sendViaEvolution(...),
    'Twilio' => self::sendViaTwilio(...),
    'Meta' => self::sendViaMeta(...),
    default => ['success' => false, 'error' => 'Provedor desconhecido']
};
```

---

### 3. Envio por Provedor

#### 🔵 Evolution API (Recomendado)

**Arquivo:** `SendWhatsAppService::sendViaEvolution()`

**Características:**
- ✅ Open Source (grátis)
- ✅ Hospedagem própria
- ✅ Sem limites de mensagens
- ✅ Popular no Brasil

**Endpoint:**
```
POST {api_url}/message/sendText/{instance_name}
```

**Headers:**
```
Content-Type: application/json
apikey: {api_key}
```

**Payload:**
```json
{
  "number": "5541999887766",
  "text": "Mensagem aqui"
}
```

**Resposta de Sucesso:**
```json
{
  "key": {
    "id": "message_id_123"
  }
}
```

**Tratamento de SSL:**
- Se `WHATSAPP_IGNORE_SSL=true` no `.env` e URL é HTTPS:
  - Ignora verificação SSL (útil para desenvolvimento com ngrok)

**Logs:**
- URL da requisição
- Payload enviado
- HTTP Code
- Resposta completa

---

#### 🟡 Twilio API

**Arquivo:** `SendWhatsAppService::sendViaTwilio()`

**Características:**
- 💰 Pago (por mensagem)
- ✅ Confiável
- ✅ Internacional
- ✅ Suporte oficial

**Endpoint:**
```
POST https://api.twilio.com/2010-04-01/Accounts/{api_key}/Messages.json
```

**Autenticação:**
```
Basic Auth: {api_key}:{api_token}
```

**Payload (form-urlencoded):**
```
From: whatsapp:+{phone_number}
To: whatsapp:+{phoneNumber}
Body: {message}
```

**Resposta de Sucesso:**
```json
{
  "sid": "message_id_123"
}
```

---

#### 🟢 Meta/Facebook API (Oficial)

**Arquivo:** `SendWhatsAppService::sendViaMeta()`

**Características:**
- ✅ API oficial do WhatsApp
- ⚠️ Requer aprovação do Facebook
- ⚠️ Processo de verificação longo
- ✅ Mais confiável para produção

**Endpoint:**
```
POST {api_url}/messages
```

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {api_token}
```

**Payload:**
```json
{
  "messaging_product": "whatsapp",
  "to": "5541999887766",
  "type": "text",
  "text": {
    "body": "Mensagem aqui"
  }
}
```

**Resposta de Sucesso:**
```json
{
  "messages": [{
    "id": "message_id_123"
  }]
}
```

---

## 📍 Onde é Usado

### 1. Recuperação de Senha
**Arquivo:** `app/adms/Controllers/Services/RecoverPassword.php`

**Fluxo:**
1. Usuário solicita recuperação de senha
2. Escolhe método: Email, WhatsApp ou Ambos
3. Sistema gera link de recuperação
4. Se escolheu WhatsApp:
   - Busca número do celular do usuário
   - Formata mensagem com link
   - Chama `SendWhatsAppService::sendMessage()`

**Exemplo de Mensagem:**
```
Prezado João,

Você solicitou a alteração de sua senha.

Para continuar, acesse o link abaixo:

👉 https://www.administrativotiaraju.kinghost.net/administrativo/reset-password/abc123?email=joao@email.com

Por questões de segurança, esse link é válido somente até as 20:00 do dia 29/01/2026.
```

---

### 2. Teste de Configuração
**Arquivo:** `app/adms/Controllers/settings/TestWhatsAppConfig.php`

**Fluxo:**
1. Usuário preenche número de teste
2. Sistema envia mensagem de teste
3. Retorna sucesso/erro

**Mensagem de Teste:**
```
✅ *Teste de Configuração WhatsApp*

Esta mensagem foi enviada automaticamente pelo Sistema Administrativo Tiaraju.

📅 Data/Hora: 29/01/2026 20:15:00

_Se você recebeu esta mensagem, a integração está funcionando corretamente!_
```

---

### 3. CRM (Futuro)
**Arquivo:** `app/adms/Controllers/crm/CrmSendWhatsApp.php`

**Uso:** Enviar mensagens para parceiros/clientes do CRM

---

## 🔧 Configurações Importantes

### Variáveis de Ambiente

**`.env`:**
```env
# Opcional: Ignorar verificação SSL em desenvolvimento
WHATSAPP_IGNORE_SSL=true  # ou false em produção
```

**Uso:**
- Útil quando usa ngrok ou servidor local com HTTPS
- Em produção, deve ser `false` para segurança

---

### Estrutura do Banco de Dados

**Tabela:** `adms_whatsapp_config`

**Campos:**
- `id` (PK)
- `api_provider` (enum: Evolution, Twilio, Meta)
- `api_url` (URL da API)
- `api_key` (Chave de autenticação)
- `api_token` (Token - opcional)
- `instance_name` (Nome da instância - Evolution)
- `phone_number` (Número WhatsApp - Twilio)
- `webhook_url` (URL para receber notificações)
- `is_active` (boolean - ativar/desativar)
- `created_at`, `updated_at`

**Lógica:**
- Apenas **1 configuração ativa** por vez (`is_active = 1`)
- Sempre busca a mais recente (`ORDER BY id DESC LIMIT 1`)

---

## 🔄 Fluxo Completo de Envio

```
1. Sistema precisa enviar WhatsApp
   │
   ▼
2. Chama SendWhatsAppService::sendMessage($phone, $message)
   │
   ▼
3. Busca configuração no banco (AdmsWhatsAppConfigRepository)
   │
   ├─ Se não configurado → Retorna erro
   │
   └─ Se configurado → Continua
   │
   ▼
4. Formata número de telefone (adiciona DDI 55 se necessário)
   │
   ▼
5. Escolhe método baseado no provedor:
   │
   ├─ Evolution → sendViaEvolution()
   │   ├─ Monta URL: {api_url}/message/sendText/{instance_name}
   │   ├─ Headers: apikey
   │   ├─ Payload: {number, text}
   │   └─ Retorna: {success, message_id, provider}
   │
   ├─ Twilio → sendViaTwilio()
   │   ├─ URL: api.twilio.com/Accounts/{api_key}/Messages.json
   │   ├─ Auth: Basic (api_key:api_token)
   │   ├─ Payload: {From, To, Body}
   │   └─ Retorna: {success, message_id, provider}
   │
   └─ Meta → sendViaMeta()
       ├─ URL: {api_url}/messages
       ├─ Headers: Authorization Bearer {api_token}
       ├─ Payload: {messaging_product, to, type, text}
       └─ Retorna: {success, message_id, provider}
   │
   ▼
6. Retorna resultado para quem chamou
   │
   ├─ success: true → Mensagem enviada
   └─ success: false → Erro (mostra mensagem)
```

---

## 🛡️ Tratamento de Erros

### Erros Comuns

1. **"WhatsApp não configurado ou desativado"**
   - Causa: `is_active = 0` ou configuração não existe
   - Solução: Ativar em `/whats-app-config`

2. **"HTTP 401" (Unauthorized)**
   - Causa: API Key/Token incorreto
   - Solução: Verificar credenciais

3. **"HTTP 404" (Not Found)**
   - Causa: URL da API incorreta ou instância não existe
   - Solução: Verificar URL e nome da instância

4. **"HTTP 0" (Connection Failed)**
   - Causa: Servidor inacessível ou SSL inválido
   - Solução: Verificar conectividade ou usar `WHATSAPP_IGNORE_SSL=true` (dev)

---

## 📝 Logs e Debug

**Onde são gerados:**
- `error_log()` do PHP
- Arquivo: `app/logs/whatsapp_config_debug.log` (WhatsAppConfig)
- Logs gerais do sistema

**Informações logadas:**
- URL da requisição
- Payload enviado
- HTTP Code recebido
- Resposta completa da API
- Número formatado
- Erros e exceções

---

## 🎯 Casos de Uso no Sistema

### 1. Recuperação de Senha
```php
// Em RecoverPassword.php
$resultWhats = SendWhatsAppService::sendMessage($phone, $mensagem);
if ($resultWhats['success']) {
    // Mensagem enviada com sucesso
}
```

### 2. Teste de Configuração
```php
// Em TestWhatsAppConfig.php
$result = SendWhatsAppService::sendMessage($testNumber, $message);
// Mostra mensagem de sucesso/erro para o usuário
```

### 3. CRM (Futuro)
```php
// Em CrmSendWhatsApp.php
$result = SendWhatsAppService::sendMessage($phoneNumber, $message);
// Envia mensagem para parceiro/cliente
```

---

## 🔐 Segurança

### Validações Implementadas

1. **CSRF Token**
   - Formulários de configuração protegidos
   - `CSRFHelper::validateCSRFToken()`

2. **Validação de Entrada**
   - Números são limpos (apenas dígitos)
   - URLs são validadas
   - Campos obrigatórios verificados

3. **SSL/TLS**
   - Em produção: SSL verificado
   - Em desenvolvimento: Pode ignorar com flag

4. **Permissões**
   - Página de configuração requer permissão
   - Apenas usuários autorizados podem configurar

---

## 📊 Estrutura de Retorno

**Sucesso:**
```php
[
    'success' => true,
    'message_id' => 'abc123',
    'provider' => 'Evolution'
]
```

**Erro:**
```php
[
    'success' => false,
    'error' => 'HTTP 401: Unauthorized'
]
```

---

## 🚀 Como Adicionar Novo Provedor

1. **Adicionar opção no formulário** (`whatsappConfig.php`)
2. **Adicionar método no Service** (`SendWhatsAppService.php`)
   ```php
   private static function sendViaNovoProvedor(...): array
   {
       // Lógica de envio
   }
   ```
3. **Adicionar no match** (`sendMessage()`)
   ```php
   'NovoProvedor' => self::sendViaNovoProvedor(...)
   ```

---

## 📚 Resumo

**Arquitetura:**
- Service Pattern (SendWhatsAppService)
- Repository Pattern (AdmsWhatsAppConfigRepository)
- Configuração centralizada no banco
- Suporte a múltiplos provedores

**Fluxo:**
1. Configurar → Salvar no banco
2. Enviar → Buscar config → Formatar número → Escolher provedor → Enviar
3. Retornar → Sucesso ou Erro

**Vantagens:**
- ✅ Flexível (3 provedores)
- ✅ Fácil de trocar provedor
- ✅ Configuração via interface
- ✅ Logs detalhados
- ✅ Tratamento de erros robusto

---

## 🔍 Exemplo Prático

**Cenário:** Usuário esqueceu a senha e escolheu receber via WhatsApp

```php
// 1. Usuário preenche formulário "Esqueci a senha"
// 2. Escolhe "WhatsApp" como método de entrega
// 3. Sistema processa em RecoverPassword.php

$phone = "41999887766"; // Número do cadastro
$url = "https://.../reset-password/abc123?email=user@email.com";
$message = "Prezado João,\n\nVocê solicitou a alteração de sua senha.\n\n👉 {$url}";

// 4. Chama o Service
$result = SendWhatsAppService::sendMessage($phone, $message);

// 5. Service internamente:
//    - Busca config no banco
//    - Formata número: "41999887766" → "5541999887766"
//    - Escolhe Evolution (provedor configurado)
//    - Faz POST para API Evolution
//    - Retorna: ['success' => true, 'message_id' => 'xyz']

// 6. Sistema mostra mensagem de sucesso
```

---

## ✅ Conclusão

A API WhatsApp do sistema é:
- **Modular:** Fácil de manter e estender
- **Flexível:** Suporta múltiplos provedores
- **Robusta:** Tratamento de erros e logs
- **Segura:** Validações e CSRF
- **Pronta para produção:** Testada e documentada

