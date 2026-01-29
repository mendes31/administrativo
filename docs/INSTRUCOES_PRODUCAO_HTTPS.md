# Instruções: Aplicar Correções em Produção para HTTPS

## Situação Atual

✅ **SSL funciona sem `www`** (sem erro de certificado)
❌ **Ainda retorna 404** (problema do `.htaccess`)

## Problemas Identificados

1. **Certificado SSL não cobre domínio com `www`**
   - `www.administrativotiaraju.kinghost.net` → Erro 51 (certificado não corresponde)
   - `administrativotiaraju.kinghost.net` → SSL funciona, mas 404

2. **`.htaccess` em produção não tem regra para URL raiz**
   - Precisa adicionar regra para capturar `/administrativo/`

---

## Solução Imediata: Aplicar Correções

### Passo 1: Atualizar `.htaccess` em Produção

**No Putty, edite o `.htaccess`:**

```bash
nano .htaccess
```

**Localize a seção (por volta da linha 35-43):**

```apache
# Permitir acesso direto à pasta scripts (apenas para diagnóstico - REMOVER EM PRODUÇÃO)
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$

# Redirecionar o resto para index.php, preservando subdiretórios e pontos
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

**Substitua por:**

```apache
# Permitir acesso direto à pasta scripts (apenas para diagnóstico - REMOVER EM PRODUÇÃO)
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$

# Regra especial para URL raiz (/administrativo/ ou /administrativo)
# Com RewriteBase /administrativo/, quando a URL é exatamente /administrativo/ ou /administrativo,
# o padrão fica vazio (^$). Esta regra captura isso e redireciona para dashboard.
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]

# Redirecionar o resto para index.php, preservando subdiretórios e pontos
# O padrão ^(.+)$ requer pelo menos 1 caractere, então não captura a URL raiz (que já foi tratada acima)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/administrativo/public/
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

**Salvar:** `Ctrl + O`, `Enter`, `Ctrl + X`

---

### Passo 2: Atualizar `.env` para usar domínio sem `www`

**No Putty:**

```bash
nano .env
```

**Altere a linha:**

```env
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Para:**

```env
URL_ADM=https://administrativotiaraju.kinghost.net/administrativo/
```

**Salvar:** `Ctrl + O`, `Enter`, `Ctrl + X`

---

### Passo 3: Testar

```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/
```

**Resultado esperado:**
- HTTP 200 ou 301/302 (não mais 404)
- Sem erro de certificado SSL

---

## Solução Definitiva (Opcional): Configurar Redirecionamento

Se quiser que `www` redirecione para sem `www` (ou vice-versa), adicione no início do `.htaccess`:

### Opção A: Redirecionar `www` → sem `www`

```apache
# Redirecionar www para sem www (se certificado só funciona sem www)
RewriteCond %{HTTP_HOST} ^www\.administrativotiaraju\.kinghost\.net [NC]
RewriteRule ^(.*)$ https://administrativotiaraju.kinghost.net%{REQUEST_URI} [R=301,L]
```

### Opção B: Redirecionar sem `www` → `www` (se certificado funcionar com www no futuro)

```apache
# Redirecionar sem www para www (se certificado funcionar com www)
RewriteCond %{HTTP_HOST} ^administrativotiaraju\.kinghost\.net [NC]
RewriteRule ^(.*)$ https://www.administrativotiaraju.kinghost.net%{REQUEST_URI} [R=301,L]
```

---

## Resumo das Alterações Necessárias

| Arquivo | Alteração | Motivo |
|---------|-----------|--------|
| `.htaccess` | Adicionar regra para URL raiz | Corrigir erro 404 |
| `.env` | Alterar para sem `www` | Certificado SSL funciona sem `www` |

---

## Teste Final

Após aplicar as correções:

```bash
# Teste 1: Verificar se funciona
curl -I https://administrativotiaraju.kinghost.net/administrativo/

# Teste 2: Verificar se redireciona para dashboard
curl -L https://administrativotiaraju.kinghost.net/administrativo/ | head -20
```

---

## Próximos Passos (Opcional)

1. **Contatar KingHost** para configurar SSL para domínio com `www`
2. **Após SSL com `www` funcionar**, alterar `.env` de volta para:
   ```env
   URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
   ```

---

## Checklist

- [ ] Atualizar `.htaccess` com regra para URL raiz
- [ ] Atualizar `.env` para usar domínio sem `www`
- [ ] Testar acesso via HTTPS
- [ ] (Opcional) Configurar redirecionamento `www` → sem `www`
- [ ] (Opcional) Contatar KingHost para SSL com `www`

