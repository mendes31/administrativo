# Diagnóstico: Redirecionamento Automático para HTTPS

## Problema

Mesmo após remover a forçagem de HTTPS no código, o sistema ainda está redirecionando para HTTPS.

## Possíveis Causas

### 1. Configuração do Servidor (Apache/Nginx)
O servidor pode estar forçando HTTPS através de:
- Configuração global do Apache/Nginx
- Arquivo `.htaccess` em diretório pai
- Configuração do painel de controle da hospedagem (KingHost)

### 2. HSTS (HTTP Strict Transport Security)
O navegador pode estar forçando HTTPS devido a:
- Header `Strict-Transport-Security` configurado anteriormente
- Cache do navegador com HSTS ativo
- Lista HSTS pré-carregada do navegador

### 3. Código Não Atualizado em Produção
O arquivo `RecoverPassword.php` pode não ter sido atualizado corretamente.

## Diagnóstico

### 1. Verificar se o código foi atualizado

**Via Putty:**
```bash
cd www/administrativo
grep -n "FORÇAR HTTPS" app/adms/Controllers/Services/RecoverPassword.php
```

**Se encontrar "FORÇAR HTTPS":**
- O código NÃO foi atualizado
- Fazer deploy novamente

**Se NÃO encontrar "FORÇAR HTTPS":**
- O código foi atualizado
- O problema está no servidor ou navegador

### 2. Verificar logs após gerar link

**Via Putty:**
```bash
tail -n 20 app/logs/*.log | grep "RecoverPassword"
```

**Deve mostrar:**
```
RecoverPassword - URL_ADM do .env: http://www.administrativotiaraju.kinghost.net/administrativo/
RecoverPassword - URL final gerada: http://www.administrativotiaraju.kinghost.net/administrativo/reset-password/...
RecoverPassword - Protocolo usado: http
```

**Se mostrar `https` no protocolo:**
- O código ainda está forçando HTTPS
- Verificar se o deploy foi feito corretamente

### 3. Verificar configuração do servidor

**Verificar se há .htaccess em diretório pai:**
```bash
cd www
ls -la .htaccess
cat .htaccess | grep -i "https\|redirect\|rewrite"
```

**Verificar configuração do Apache (se tiver acesso):**
```bash
# Verificar se há redirecionamento global
grep -r "RewriteRule.*https" /etc/apache2/ 2>/dev/null
```

### 4. Verificar headers HTTP

**Testar com curl:**
```bash
curl -I http://www.administrativotiaraju.kinghost.net/administrativo/
```

**Verificar se retorna:**
- `Location: https://...` (redirecionamento)
- `Strict-Transport-Security` (HSTS ativo)

## Soluções

### Solução 1: Verificar e Limpar Cache do Navegador

1. **Chrome/Edge:**
   - Pressione `Ctrl+Shift+Delete`
   - Selecione "Imagens e arquivos em cache"
   - Limpar dados

2. **Firefox:**
   - Pressione `Ctrl+Shift+Delete`
   - Selecione "Cache"
   - Limpar agora

3. **Limpar HSTS:**
   - Chrome: `chrome://net-internals/#hsts`
   - Digite o domínio: `administrativotiaraju.kinghost.net`
   - Clique em "Delete"

### Solução 2: Verificar Configuração da KingHost

A KingHost pode ter configuração global forçando HTTPS. Verificar no painel de controle:
- Configurações de SSL/HTTPS
- Redirecionamento automático HTTP → HTTPS
- Desabilitar se necessário

### Solução 3: Adicionar Exceção no .htaccess

Se o servidor estiver forçando HTTPS, podemos adicionar exceção para links de recuperação:

```apache
# Permitir HTTP apenas para reset-password (se necessário)
RewriteCond %{REQUEST_URI} ^/administrativo/reset-password
RewriteCond %{HTTPS} on
RewriteRule ^(.*)$ http://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

⚠️ **ATENÇÃO:** Isso pode causar problemas de segurança. Use apenas se necessário.

### Solução 4: Aceitar HTTPS e Configurar Certificado

Se o servidor está forçando HTTPS, a melhor solução é:
1. Configurar certificado SSL válido
2. Atualizar `.env` para `https://`
3. Aceitar que os links serão HTTPS

## Próximos Passos

1. **Verificar logs** (comando acima)
2. **Verificar código em produção** (comando acima)
3. **Testar em navegador anônimo/privado** (para evitar cache/HSTS)
4. **Verificar painel KingHost** (configurações de SSL)

## Comandos Rápidos

```bash
# 1. Verificar código
grep -n "FORÇAR HTTPS\|Protocolo usado" app/adms/Controllers/Services/RecoverPassword.php

# 2. Verificar logs
tail -n 30 app/logs/*.log | grep "RecoverPassword"

# 3. Testar redirecionamento
curl -I http://www.administrativotiaraju.kinghost.net/administrativo/

# 4. Verificar .htaccess pai
ls -la ../.htaccess
cat ../.htaccess 2>/dev/null | grep -i "https\|redirect"
```

