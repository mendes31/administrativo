# Verificação: Preparação do Código para HTTPS

## ✅ Status Geral: **PRONTO PARA HTTPS**

O código já está preparado para usar HTTPS. Apenas **1 ajuste menor** é necessário.

---

## 📋 Verificações Realizadas

### ✅ 1. Uso de `$_ENV['URL_ADM']` no Código

**Status:** ✅ **CORRETO**

Todos os pontos críticos já usam a variável de ambiente:

- ✅ **Recuperação de Senha** (`RecoverPassword.php`): Usa `$_ENV['URL_ADM']`
- ✅ **Templates de Email**: Usam `$_ENV['URL_ADM']`
  - `EvaluationNotificationService.php`
  - `StrategicPlanNotificationService.php`
- ✅ **Layouts**: Usam `$_ENV['URL_ADM']`
  - `main.php`
  - `login.php`
- ✅ **Views**: Usam `$_ENV['URL_ADM']` para links e assets

**Conclusão:** Ao alterar o `.env` para `https://`, todas as URLs serão atualizadas automaticamente.

---

### ⚠️ 2. Ajuste Necessário: `.htaccess`

**Status:** ⚠️ **AJUSTE RECOMENDADO**

**Arquivo:** `.htaccess` (linha 40)

**Situação Atual:**
```apache
ErrorDocument 403 http://www.administrativotiaraju.kinghost.net/administrativo/error403
```

**Problema:**
- URL hardcoded com `http://`
- Se migrar para HTTPS, o erro 403 ainda redirecionará para HTTP
- Pode causar avisos de segurança ou redirecionamentos desnecessários

**Solução:**
Usar caminho relativo (já funciona com HTTP e HTTPS):

```apache
ErrorDocument 403 /administrativo/error403
```

**Impacto:**
- ✅ Funciona com HTTP e HTTPS
- ✅ Não precisa de ajuste ao migrar
- ✅ Mais flexível

---

### ✅ 3. Redirecionamentos HTTP → HTTPS

**Status:** ✅ **OPCIONAL (Já documentado)**

Se quiser forçar redirecionamento HTTP → HTTPS após configurar SSL, adicione no `.htaccess`:

```apache
# Forçar HTTPS (descomentar APENAS após SSL estar configurado)
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteCond %{HTTP_HOST} ^(www\.)?administrativotiaraju\.kinghost\.net [NC]
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

⚠️ **IMPORTANTE:** Só descomente isso **DEPOIS** de ter SSL válido configurado no servidor!

---

### ✅ 4. JavaScript e AJAX

**Status:** ✅ **CORRETO**

- Views usam `$_ENV['URL_ADM']` em JavaScript quando necessário
- URLs relativas são usadas onde possível
- Não há URLs hardcoded com `http://` ou `https://` em JavaScript

---

### ✅ 5. Serviços de Email e Notificações

**Status:** ✅ **CORRETO**

Todos os serviços que enviam links por email já usam `$_ENV['URL_ADM']`:

- ✅ `RecoverPassword.php` (recuperação de senha)
- ✅ `EvaluationNotificationService.php` (notificações de avaliação)
- ✅ `StrategicPlanNotificationService.php` (notificações de plano estratégico)

**Conclusão:** Links em emails serão automaticamente atualizados para HTTPS ao alterar o `.env`.

---

### ✅ 6. WhatsApp Links

**Status:** ✅ **CORRETO**

O `RecoverPassword.php` já:
- Usa `$_ENV['URL_ADM']` para construir URLs
- Garante que a URL tenha protocolo (`http://` ou `https://`)
- Formata o link corretamente para WhatsApp

**Conclusão:** Links via WhatsApp serão automaticamente atualizados para HTTPS.

---

## 🔧 Ajustes Necessários

### Ajuste 1: `.htaccess` - ErrorDocument 403

**Arquivo:** `.htaccess`

**Linha 40:** Alterar de:
```apache
ErrorDocument 403 http://www.administrativotiaraju.kinghost.net/administrativo/error403
```

**Para:**
```apache
ErrorDocument 403 /administrativo/error403
```

**Por quê?**
- Funciona com HTTP e HTTPS
- Não precisa de ajuste ao migrar
- Mais flexível e portável

---

## 📝 Checklist para Migração

### Antes de Migrar:
- [ ] Verificar se SSL está configurado no servidor
- [ ] Testar acesso via HTTPS no navegador
- [ ] Ajustar `.htaccess` (ErrorDocument 403)

### Durante a Migração:
- [ ] Alterar `.env`: `URL_ADM=https://...`
- [ ] Testar acesso ao sistema
- [ ] Testar links de recuperação de senha
- [ ] Testar links em emails
- [ ] Testar links via WhatsApp

### Após Migrar (Opcional):
- [ ] Adicionar regra de redirecionamento HTTP → HTTPS no `.htaccess`
- [ ] Testar redirecionamento
- [ ] Verificar logs para erros

---

## ✅ Conclusão

**O código está 99% pronto para HTTPS!**

**Apenas 1 ajuste recomendado:**
- Alterar `ErrorDocument 403` no `.htaccess` para usar caminho relativo

**Ao alterar o `.env` para `https://`:**
- ✅ Todas as URLs serão atualizadas automaticamente
- ✅ Links em emails funcionarão com HTTPS
- ✅ Links via WhatsApp funcionarão com HTTPS
- ✅ Assets (CSS, JS, imagens) carregarão via HTTPS
- ✅ Redirecionamentos usarão HTTPS

**Próximo passo:** Configurar SSL no servidor e alterar o `.env`!

