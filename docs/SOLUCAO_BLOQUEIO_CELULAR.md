# Solução para Bloqueio de Links em Celulares

## Problema Identificado

Os celulares estão bloqueando o acesso aos links enviados via WhatsApp, mesmo que o link esteja clicável.

## Possíveis Causas

### 1. Certificado SSL Inválido ou Auto-Assinado
**Sintoma:** Navegador móvel mostra aviso de segurança ou bloqueia completamente.

**Solução:**
- Verificar se o certificado SSL está válido e não expirado
- Certificado deve ser emitido por uma CA confiável (não auto-assinado)
- Verificar em: https://www.sslshopper.com/ssl-checker.html

### 2. URL sem Protocolo HTTPS
**Sintoma:** Link não é reconhecido como clicável ou abre sem segurança.

**Solução:**
- ✅ Já corrigido no código: `RecoverPassword.php` agora garante protocolo HTTPS
- Verificar se `URL_ADM` no `.env` de produção tem `https://`

### 3. Domínio Incorreto no .env
**Sintoma:** Link aponta para domínio errado (ex: `raju.kinghost.net` em vez de `www.administrativotiaraju.kinghost.net`).

**Solução:**
- Verificar `.env` em produção:
```bash
# Em produção, via Putty:
cat .env | grep URL_ADM
```

- Deve estar assim:
```
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

- **NÃO deve estar assim:**
```
URL_ADM=http://raju.kinghost.net/administrativo/
URL_ADM=raju.kinghost.net/administrativo/
```

### 4. Firewall ou Bloqueio do Provedor
**Sintoma:** Link funciona em WiFi mas não em dados móveis (ou vice-versa).

**Solução:**
- Verificar se o domínio não está em lista negra
- Testar em diferentes operadoras
- Verificar logs do servidor para ver se requisições chegam

## Correções Implementadas

### 1. Validação de Protocolo
```php
// Garantir que a URL tenha protocolo (http:// ou https://)
if (!empty($baseUrl) && !preg_match('/^https?:\/\//i', $baseUrl)) {
    $baseUrl = 'https://' . ltrim($baseUrl, '/');
}
```

### 2. Correção Automática de Domínio
```php
// Se a URL contém "raju.kinghost.net" (domínio incorreto), substituir pelo correto
if (strpos($baseUrl, 'raju.kinghost.net') !== false) {
    $baseUrl = str_replace('raju.kinghost.net', 'www.administrativotiaraju.kinghost.net', $baseUrl);
}
```

### 3. Garantir Caminho Completo
```php
// Garantir que a URL termine com /administrativo/
if (!empty($baseUrl) && strpos($baseUrl, '/administrativo') === false) {
    $baseUrl = rtrim($baseUrl, '/') . '/administrativo';
}
```

### 4. Correção do ErrorDocument 403 (HTTPS)
**Arquivo:** `.htaccess`
```apache
# ANTES (causava bloqueio em mobile):
ErrorDocument 403 http://www.administrativotiaraju.kinghost.net/administrativo/error403

# DEPOIS (corrigido):
ErrorDocument 403 https://www.administrativotiaraju.kinghost.net/administrativo/error403
```

### 5. Headers de Segurança para Mobile
**Arquivo:** `.htaccess`
```apache
# Adicionado header para permitir acesso de dispositivos móveis
Header always set Access-Control-Allow-Origin "*"
```

## Problema Específico: Funciona em Desktop, Não em Mobile

Se o link funciona em computadores mas não em celulares, as causas mais comuns são:

### 1. Certificado SSL Não Confiável em Mobile
**Sintoma:** Desktop aceita, mobile bloqueia.

**Solução:**
- Verificar certificado em: https://www.sslshopper.com/ssl-checker.html#hostname=www.administrativotiaraju.kinghost.net
- Certificado deve ser emitido por CA confiável (Let's Encrypt, DigiCert, etc.)
- Não pode ser auto-assinado

### 2. Mixed Content (HTTP dentro de HTTPS)
**Sintoma:** Link abre mas recursos (CSS, JS) não carregam.

**Solução:**
- ✅ Já corrigido: `ErrorDocument 403` agora usa HTTPS
- Verificar se todos os recursos usam HTTPS

### 3. Headers de Segurança Muito Restritivos
**Sintoma:** Mobile bloqueia completamente, desktop funciona.

**Solução:**
- ✅ Já corrigido: Adicionado `Access-Control-Allow-Origin "*"` no `.htaccess`
- Verificar se não há bloqueio de User-Agent mobile

### 4. Redirecionamento HTTP -> HTTPS
**Sintoma:** Mobile não segue redirecionamento.

**Solução:**
- Verificar se servidor força HTTPS
- Adicionar regra no `.htaccess` (comentada, descomentar se necessário):
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteCond %{HTTP_HOST} ^(www\.)?administrativotiaraju\.kinghost\.net [NC]
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

## Verificação em Produção

### 1. Verificar .env
```bash
cd www/administrativo
cat .env | grep URL_ADM
```

**Deve mostrar:**
```
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

### 2. Testar Certificado SSL
Acesse: https://www.sslshopper.com/ssl-checker.html#hostname=www.administrativotiaraju.kinghost.net

Verifique:
- ✅ Certificado válido
- ✅ Não expirado
- ✅ Emitido por CA confiável
- ✅ Sem erros de cadeia

### 3. Testar Link Manualmente
1. Gere um link de recuperação de senha
2. Verifique os logs: `tail -n 50 app/logs/*.log | grep "RecoverPassword"`
3. Copie a URL gerada e teste em:
   - Navegador desktop
   - Navegador móvel (WiFi)
   - Navegador móvel (dados móveis)

### 4. Verificar Headers de Segurança
```bash
curl -I https://www.administrativotiaraju.kinghost.net/administrativo/
```

Verifique se retorna:
- `HTTP/2 200` ou `HTTP/1.1 200`
- Headers de segurança adequados

## Solução Temporária

Se o problema persistir, você pode:

1. **Enviar link completo no corpo da mensagem:**
   - Em vez de apenas o link, incluir instruções: "Copie e cole este link no navegador: [URL]"

2. **Usar encurtador de URL:**
   - Serviços como bit.ly ou tinyurl podem ajudar a contornar bloqueios
   - ⚠️ **Atenção:** Verificar política de segurança antes de usar

3. **Verificar configuração do servidor:**
   - Headers de segurança podem estar muito restritivos
   - Verificar `.htaccess` e configuração do Apache

## Próximos Passos

1. **Verificar .env em produção:**
   ```bash
   cat .env | grep URL_ADM
   ```

2. **Se estiver incorreto, corrigir:**
   ```bash
   # Editar .env
   nano .env
   # Ou usar editor de sua preferência
   ```

3. **Testar após correção:**
   - Gerar novo link de recuperação
   - Testar em celular
   - Verificar logs

## Logs para Diagnóstico

Os logs agora incluem:
- URL gerada completa
- Se tem protocolo
- Se domínio foi corrigido

Verificar em:
```bash
tail -n 100 app/logs/*.log | grep "RecoverPassword"
```

