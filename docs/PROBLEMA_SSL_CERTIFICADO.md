# Problema: Certificado SSL não corresponde ao domínio

## Erro Identificado

```
curl: (51) SSL: no alternative certificate subject name matches target host name 'www.administrativotiaraju.kinghost.net'
```

## Significado

O certificado SSL instalado no servidor **não cobre** o domínio `www.administrativotiaraju.kinghost.net`.

## Possíveis Causas

### 1. Certificado configurado para domínio sem `www`
- Certificado pode estar configurado para: `administrativotiaraju.kinghost.net` (sem `www`)
- Mas você está acessando: `www.administrativotiaraju.kinghost.net` (com `www`)

### 2. Certificado configurado para outro domínio
- Certificado pode estar para: `raju.kinghost.net` ou outro domínio
- Não cobre o domínio correto

### 3. Certificado não instalado
- SSL pode não estar configurado para este domínio específico

---

## Diagnóstico

### Teste 1: Verificar sem `www`

```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/
```

**Se funcionar:**
- Certificado está configurado para domínio sem `www`
- Solução: Usar domínio sem `www` no `.env` ou configurar redirecionamento

### Teste 2: Verificar detalhes do certificado

```bash
openssl s_client -connect www.administrativotiaraju.kinghost.net:443 -servername www.administrativotiaraju.kinghost.net 2>&1 | grep -i "subject\|issuer\|CN="
```

**Mostrará:**
- Para qual domínio o certificado foi emitido
- Quem emitiu o certificado
- Se é válido

### Teste 3: Verificar certificado sem `www`

```bash
openssl s_client -connect administrativotiaraju.kinghost.net:443 -servername administrativotiaraju.kinghost.net 2>&1 | grep -i "subject\|issuer\|CN="
```

---

## Soluções

### Solução 1: Usar domínio sem `www` (Temporário)

Se o certificado funciona sem `www`, altere o `.env`:

```env
URL_ADM=https://administrativotiaraju.kinghost.net/administrativo/
```

**Vantagens:**
- Funciona imediatamente
- Não precisa configurar nada no servidor

**Desvantagens:**
- Usuários podem acessar com `www` e ver erro
- Não é a solução ideal

---

### Solução 2: Configurar certificado para ambos os domínios (Recomendado)

**Ação necessária:**
- Contatar suporte da KingHost
- Solicitar certificado SSL que cubra:
  - `www.administrativotiaraju.kinghost.net`
  - `administrativotiaraju.kinghost.net` (opcional, mas recomendado)

**O que pedir:**
- "Preciso configurar SSL para o domínio `www.administrativotiaraju.kinghost.net`"
- "O certificado atual não cobre o domínio com `www`"
- "Preciso de um certificado que cubra tanto `www` quanto sem `www`" (wildcard ou SAN)

---

### Solução 3: Configurar redirecionamento no servidor

Se o certificado funciona sem `www`, configure redirecionamento:

**No `.htaccess` (adicionar no início):**
```apache
# Redirecionar www para sem www (se certificado só funciona sem www)
RewriteCond %{HTTP_HOST} ^www\.administrativotiaraju\.kinghost\.net [NC]
RewriteRule ^(.*)$ https://administrativotiaraju.kinghost.net%{REQUEST_URI} [R=301,L]
```

**Ou redirecionar sem www para www (se certificado funciona com www):**
```apache
# Redirecionar sem www para www (se certificado só funciona com www)
RewriteCond %{HTTP_HOST} ^administrativotiaraju\.kinghost\.net [NC]
RewriteRule ^(.*)$ https://www.administrativotiaraju.kinghost.net%{REQUEST_URI} [R=301,L]
```

---

## Testes para Executar

### 1. Testar sem `www`:
```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/
```

### 2. Verificar certificado com `www`:
```bash
openssl s_client -connect www.administrativotiaraju.kinghost.net:443 -servername www.administrativotiaraju.kinghost.net 2>&1 | grep -A 5 "Certificate:"
```

### 3. Verificar certificado sem `www`:
```bash
openssl s_client -connect administrativotiaraju.kinghost.net:443 -servername administrativotiaraju.kinghost.net 2>&1 | grep -A 5 "Certificate:"
```

---

## Próximos Passos

1. **Testar sem `www`** para confirmar se funciona
2. **Verificar qual domínio o certificado cobre** usando `openssl`
3. **Decidir a solução:**
   - Se funciona sem `www`: Alterar `.env` ou configurar redirecionamento
   - Se não funciona: Contatar KingHost para configurar SSL corretamente

---

## Resumo

| Situação | Ação |
|----------|------|
| Certificado funciona sem `www` | Alterar `.env` para sem `www` ou configurar redirecionamento |
| Certificado não funciona | Contatar KingHost para configurar SSL |
| Certificado funciona com `www` | Manter `.env` com `www` |

---

## Contato com KingHost

**O que informar:**
- "O certificado SSL não está funcionando para `www.administrativotiaraju.kinghost.net`"
- "Preciso de um certificado SSL válido que cubra o domínio com `www`"
- "Erro: `SSL: no alternative certificate subject name matches target host name`"

**Solicitar:**
- Certificado SSL para `www.administrativotiaraju.kinghost.net`
- Ou certificado que cubra ambos (`www` e sem `www`)

