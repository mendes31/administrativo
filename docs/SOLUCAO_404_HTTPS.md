# Solução: Erro 404 ao Acessar via HTTPS

## Problema Identificado

Ao acessar `https://www.administrativotiaraju.kinghost.net/administrativo/`, o sistema retorna erro 404 "Not Found".

## Possíveis Causas

### 1. ✅ **URL Raiz não Capturada pelo `.htaccess`** (CORRIGIDO)

**Problema:**
- A regra `RewriteRule ^(.+)$` requer pelo menos 1 caractere
- A URL raiz (`/administrativo/`) não tem nada depois, então não é capturada
- Resultado: 404 Not Found

**Solução Aplicada:**
Adicionada regra específica para URL raiz no `.htaccess`:

```apache
# Regra especial para URL raiz
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]
```

---

### 2. ⚠️ **Problema de Porta (HTTPS na Porta 443)**

**Possível Causa:**
- HTTPS usa a porta **443** por padrão
- HTTP usa a porta **80** por padrão
- Se o servidor não estiver configurado para aceitar HTTPS na porta 443, pode retornar 404

**Como Verificar:**

#### Teste 1: Acessar com porta explícita
```
https://www.administrativotiaraju.kinghost.net:443/administrativo/
```

#### Teste 2: Verificar se HTTPS está funcionando
```bash
# No Putty, testar conexão HTTPS
curl -I https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Se retornar erro de conexão:**
- SSL/HTTPS não está configurado no servidor
- Precisa configurar SSL primeiro

**Se retornar HTTP 200 ou 301/302:**
- HTTPS está funcionando
- O problema é apenas no `.htaccess`

---

### 3. ⚠️ **Configuração do Apache para HTTPS**

**Possível Causa:**
- O Apache pode não estar configurado para processar requisições HTTPS
- O VirtualHost pode não estar configurado para a porta 443

**Como Verificar (se tiver acesso root):**
```bash
# Verificar se o Apache está escutando na porta 443
netstat -tuln | grep 443

# Verificar configuração do Apache
cat /etc/apache2/sites-available/*.conf | grep -i "443\|ssl"
```

**Solução:**
- Configurar VirtualHost para HTTPS na porta 443
- Habilitar módulo SSL do Apache: `a2enmod ssl`
- Reiniciar Apache: `systemctl restart apache2`

---

### 4. ⚠️ **Certificado SSL Inválido ou Não Configurado**

**Possível Causa:**
- Certificado SSL não está instalado
- Certificado está expirado
- Certificado não corresponde ao domínio

**Como Verificar:**
1. Acesse: https://www.sslshopper.com/ssl-checker.html
2. Digite: `www.administrativotiaraju.kinghost.net`
3. Veja o status do certificado

**Solução:**
- Instalar certificado SSL válido (Let's Encrypt recomendado)
- Verificar se o certificado corresponde ao domínio correto

---

## Soluções Aplicadas

### ✅ Correção 1: Regra para URL Raiz no `.htaccess`

**Arquivo:** `.htaccess`

**Adicionado:**
```apache
# Regra especial para URL raiz (/administrativo/ ou /administrativo)
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]
```

**Por quê:**
- A regra geral `^(.+)$` não captura URLs vazias
- A URL raiz precisa de tratamento especial
- Redireciona para `dashboard` quando acessar apenas `/administrativo/`

---

## Próximos Passos

### 1. Verificar se o Problema foi Resolvido

Após aplicar a correção no `.htaccess` em produção:

1. **Teste HTTP (deve funcionar):**
   ```
   http://www.administrativotiaraju.kinghost.net/administrativo/
   ```

2. **Teste HTTPS:**
   ```
   https://www.administrativotiaraju.kinghost.net/administrativo/
   ```

### 2. Se HTTPS Ainda Não Funcionar

#### Opção A: Verificar SSL no Servidor
```bash
# No Putty
curl -I https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Se retornar erro de conexão:**
- SSL não está configurado
- Entre em contato com a KingHost para configurar SSL

#### Opção B: Verificar Logs do Apache
```bash
# Verificar logs de erro
tail -n 50 /var/log/apache2/error.log

# Ou logs do servidor (dependendo da configuração)
tail -n 50 /home/administrativotiaraju/logs/error.log
```

### 3. Se o Problema Persistir

**Verificar:**
1. ✅ `.htaccess` foi atualizado em produção?
2. ✅ `.env` tem `URL_ADM=https://...`?
3. ✅ SSL está configurado no servidor?
4. ✅ Apache está escutando na porta 443?

**Diagnóstico Adicional:**
```bash
# No Putty, verificar se o arquivo index.php existe
ls -la index.php

# Verificar permissões
ls -la .htaccess

# Verificar se o Apache pode ler os arquivos
cat .htaccess | head -20
```

---

## Resumo

| Item | Status | Ação |
|------|--------|------|
| Regra URL raiz no `.htaccess` | ✅ Corrigido | Aplicar em produção |
| SSL configurado | ⚠️ Verificar | Testar com `curl -I https://...` |
| Porta 443 funcionando | ⚠️ Verificar | Testar acesso HTTPS |
| `.env` com HTTPS | ✅ Verificado | Já alterado |

---

## Comandos Úteis para Diagnóstico

```bash
# 1. Testar conexão HTTPS
curl -I https://www.administrativotiaraju.kinghost.net/administrativo/

# 2. Verificar se porta 443 está aberta
netstat -tuln | grep 443

# 3. Verificar logs do Apache (se tiver acesso)
tail -n 100 /var/log/apache2/error.log

# 4. Verificar certificado SSL
openssl s_client -connect www.administrativotiaraju.kinghost.net:443 -servername www.administrativotiaraju.kinghost.net

# 5. Verificar se index.php existe e tem permissões corretas
ls -la index.php
```

---

## Conclusão

**Problema Principal:** URL raiz não estava sendo capturada pelo `.htaccess` ✅ **CORRIGIDO**

**Próximo Passo:** Aplicar a correção em produção e testar.

**Se ainda não funcionar:** Verificar configuração SSL no servidor (KingHost).

