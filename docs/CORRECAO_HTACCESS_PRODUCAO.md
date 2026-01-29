# Correção Final: .htaccess em Produção

## Problema

O `.htaccess` já tem a regra para URL raiz, mas ainda retorna 404. Isso pode ser devido à ordem ou lógica das regras.

## Solução: Reorganizar Regras

A regra para URL raiz precisa estar **ANTES** das condições gerais e ter uma lógica mais simples.

### Versão Corrigida do `.htaccess`

**No Putty, edite o `.htaccess`:**

```bash
nano .htaccess
```

**Substitua a seção das linhas 25-47 por:**

```apache
# Regra especial para URL raiz - DEVE VIR PRIMEIRO
# Captura /administrativo/ ou /administrativo e redireciona para dashboard
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]

# Não reescrever se for arquivo ou diretório físico
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Permitir acesso direto à pasta public (assets estáticos)
RewriteCond %{REQUEST_URI} !^/administrativo/public/

# Permitir acesso direto à pasta scripts (apenas para diagnóstico - REMOVER EM PRODUÇÃO)
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$

# Redirecionar o resto para index.php, preservando subdiretórios e pontos
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

**A seção completa deve ficar assim (linhas 25-47):**

```apache
# Regra especial para URL raiz - DEVE VIR PRIMEIRO
# Captura /administrativo/ ou /administrativo e redireciona para dashboard
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]

# Não reescrever se for arquivo ou diretório físico
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Permitir acesso direto à pasta public (assets estáticos)
RewriteCond %{REQUEST_URI} !^/administrativo/public/

# Permitir acesso direto à pasta scripts (apenas para diagnóstico - REMOVER EM PRODUÇÃO)
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$

# Redirecionar o resto para index.php, preservando subdiretórios e pontos
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

**Salvar:** `Ctrl + O`, `Enter`, `Ctrl + X`

---

## Diferença Principal

**Antes:** A regra da URL raiz estava DEPOIS das condições gerais
**Agora:** A regra da URL raiz está PRIMEIRO, antes de qualquer outra condição

**Por quê?**
- O Apache processa as regras em ordem
- Se a URL raiz for capturada primeiro, não precisa passar pelas outras condições
- Isso garante que `/administrativo/` seja sempre redirecionado para `dashboard`

---

## Teste Após Correção

```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/
```

**Resultado esperado:**
- HTTP 200 ou 301/302 (não mais 404)
- Redirecionamento para dashboard

---

## Se Ainda Não Funcionar

### Teste Alternativo: Verificar se index.php existe

```bash
ls -la index.php
```

### Teste: Acessar index.php diretamente

```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/index.php?url=dashboard
```

**Se funcionar:**
- O problema é apenas na regra do `.htaccess`
- A correção acima deve resolver

**Se não funcionar:**
- Pode haver problema com o PHP ou configuração do servidor
- Verificar logs do Apache

---

## Checklist

- [ ] Reorganizar regras no `.htaccess` (regra URL raiz primeiro)
- [ ] Testar acesso via HTTPS
- [ ] Verificar se redireciona para dashboard
- [ ] (Se necessário) Verificar logs do Apache

