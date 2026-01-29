# Guia: Migração para HTTPS

## Resumo Rápido

**Para usar HTTPS, você precisa:**
1. ✅ Alterar o `.env` (mudar `http://` para `https://`)
2. ✅ Verificar se o servidor tem certificado SSL configurado
3. ⚠️ **SSL válido é recomendado**, mas tecnicamente pode funcionar sem (com avisos)

---

## 1. Alterar o `.env`

### No servidor (via Putty):

```bash
nano .env
```

Altere a linha:
```env
URL_ADM=http://www.administrativotiaraju.kinghost.net/administrativo/
```

Para:
```env
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

Salve: `Ctrl + O`, `Enter`, `Ctrl + X`

---

## 2. Verificar se o Servidor tem SSL Configurado

### Teste 1: Acessar via HTTPS no navegador
```
https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Resultados possíveis:**

#### ✅ **Cenário A: Funciona com cadeado verde**
- Servidor já tem SSL válido configurado
- **Ação:** Apenas alterar o `.env` e está pronto!

#### ⚠️ **Cenário B: Funciona mas mostra aviso de segurança**
- Servidor tem SSL, mas certificado é auto-assinado ou inválido
- **Ação:** 
  - Pode funcionar, mas navegadores vão mostrar avisos
  - Celulares podem bloquear os links
  - **Recomendação:** Instalar certificado válido (Let's Encrypt gratuito)

#### ❌ **Cenário C: Erro de conexão / Não carrega**
- Servidor não tem SSL configurado
- **Ação:** Precisa configurar SSL primeiro (ver seção 3)

---

## 3. Sobre SSL (Certificado de Segurança)

### O que é SSL?
- Certificado que criptografa a comunicação entre navegador e servidor
- Garante que os dados não sejam interceptados

### Preciso de SSL obrigatoriamente?

**Tecnicamente: NÃO**
- Você pode usar `https://` sem certificado válido
- O navegador vai mostrar aviso de "Conexão não segura"
- O usuário pode clicar em "Avançar mesmo assim" e acessar

**Na prática: SIM (recomendado)**
- ⚠️ **Celulares bloqueiam links HTTPS sem certificado válido**
- ⚠️ **Navegadores modernos mostram avisos assustadores**
- ⚠️ **WhatsApp pode não tornar links clicáveis**
- ⚠️ **Sites sem SSL perdem ranking no Google**

### Opções de SSL

#### 1. **Let's Encrypt (GRATUITO)** ⭐ Recomendado
- Certificado válido e confiável
- Renovação automática
- Suportado por todos os navegadores

**Como obter:**
- Verificar se o painel da KingHost oferece Let's Encrypt
- Ou instalar via SSH (se tiver acesso root)

#### 2. **Certificado Pago**
- Comprar de uma CA (Autoridade Certificadora)
- Exemplos: Comodo, DigiCert, GlobalSign
- Custa de R$ 50 a R$ 500/ano

#### 3. **Certificado Auto-Assinado** (NÃO recomendado)
- Você mesmo gera o certificado
- Funciona, mas navegadores mostram avisos
- Celulares bloqueiam

---

## 4. Passos para Migração Completa

### Passo 1: Verificar SSL no Servidor
```bash
# No Putty, testar se HTTPS responde
curl -I https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Se retornar erro:** Precisa configurar SSL primeiro.

**Se retornar HTTP 200 ou 301/302:** SSL está funcionando.

### Passo 2: Alterar `.env`
```bash
nano .env
# Alterar URL_ADM para https://
```

### Passo 3: Atualizar `.htaccess` (Opcional)
Se quiser forçar redirecionamento HTTP → HTTPS, adicione no `.htaccess`:

```apache
# Forçar HTTPS (descomentar se SSL estiver configurado)
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteCond %{HTTP_HOST} ^(www\.)?administrativotiaraju\.kinghost\.net [NC]
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

⚠️ **ATENÇÃO:** Só descomente isso se tiver SSL válido configurado!

### Passo 4: Testar
1. Acesse: `https://www.administrativotiaraju.kinghost.net/administrativo/`
2. Verifique se aparece o cadeado verde
3. Teste links de recuperação de senha
4. Teste no celular

---

## 5. O que Acontece SEM SSL Válido?

### No Computador:
- ✅ Site funciona
- ⚠️ Navegador mostra aviso: "Sua conexão não é privada"
- ⚠️ Usuário precisa clicar em "Avançar mesmo assim"

### No Celular:
- ❌ **Links podem ser bloqueados** (especialmente WhatsApp)
- ❌ Navegador mostra aviso mais agressivo
- ❌ Alguns apps bloqueiam completamente

### No WhatsApp:
- ❌ Links podem não ficar clicáveis
- ❌ Ao clicar, pode abrir aviso de segurança
- ❌ Usuário pode desistir de acessar

---

## 6. Recomendação Final

### ✅ **CENÁRIO IDEAL:**
1. Configurar SSL válido (Let's Encrypt)
2. Alterar `.env` para `https://`
3. Testar tudo
4. **Resultado:** Links funcionam perfeitamente em todos os dispositivos

### ⚠️ **CENÁRIO TEMPORÁRIO (sem SSL válido):**
1. Alterar `.env` para `https://`
2. Usuários vão ver avisos de segurança
3. Celulares podem bloquear links
4. **Resultado:** Funciona, mas com limitações

### ❌ **NÃO RECOMENDADO:**
- Usar HTTPS sem certificado válido em produção
- Especialmente se o sistema envia links via WhatsApp

---

## 7. Verificar SSL Atual

### Teste Online:
1. Acesse: https://www.sslshopper.com/ssl-checker.html
2. Digite: `www.administrativotiaraju.kinghost.net`
3. Veja o status do certificado

### Teste via Terminal:
```bash
# Verificar certificado
openssl s_client -connect www.administrativotiaraju.kinghost.net:443 -servername www.administrativotiaraju.kinghost.net
```

---

## 8. Próximos Passos

1. **Teste primeiro:** Acesse `https://www.administrativotiaraju.kinghost.net/administrativo/` no navegador
2. **Se funcionar com cadeado verde:** Apenas altere o `.env`
3. **Se não funcionar ou mostrar aviso:** Entre em contato com a KingHost para configurar SSL
4. **Se a KingHost oferecer Let's Encrypt:** Ative no painel (geralmente é gratuito)

---

## Resumo

| Item | Obrigatório? | Impacto |
|------|--------------|---------|
| Alterar `.env` | ✅ Sim | Todas as URLs do sistema |
| SSL válido | ⚠️ Recomendado | Links funcionam em celulares |
| Forçar HTTPS no `.htaccess` | ❌ Opcional | Redireciona HTTP → HTTPS |

**Conclusão:** Para produção, especialmente com links via WhatsApp, **SSL válido é essencial**.

