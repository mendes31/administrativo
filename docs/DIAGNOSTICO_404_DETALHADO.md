# Diagnóstico Detalhado: Erro 404 Persistente

## Situação

Mesmo após reorganizar as regras, ainda retorna 404.

## Testes de Diagnóstico

### Teste 1: Verificar se index.php existe e é acessível

```bash
# Verificar se o arquivo existe
ls -la index.php

# Tentar acessar index.php diretamente
curl -I https://administrativotiaraju.kinghost.net/administrativo/index.php?url=dashboard
```

**Se funcionar:**
- O `index.php` está OK
- O problema é apenas na regra do `.htaccess`

**Se não funcionar:**
- Pode haver problema com PHP ou configuração do servidor

---

### Teste 2: Verificar se o .htaccess está sendo processado

```bash
# Adicionar uma regra de teste que sempre redireciona
# (temporário, apenas para diagnóstico)
```

Adicione no início do `.htaccess` (após `RewriteEngine On`):

```apache
# TESTE: Se esta regra funcionar, o .htaccess está sendo processado
RewriteRule ^teste-htaccess$ /administrativo/index.php?url=dashboard [R=302,L]
```

Depois teste:
```bash
curl -I https://administrativotiaraju.kinghost.net/administrativo/teste-htaccess
```

**Se redirecionar:**
- `.htaccess` está sendo processado
- O problema é na regra específica para URL raiz

**Se não redirecionar:**
- `.htaccess` pode não estar sendo processado
- Verificar configuração do Apache

---

### Teste 3: Verificar logs do Apache

```bash
# Verificar logs de erro (caminho pode variar)
tail -n 50 /var/log/apache2/error.log

# Ou logs do usuário (se houver)
tail -n 50 ~/logs/error.log
```

---

## Solução Alternativa: Regra Mais Simples

Se os testes acima indicarem que o problema é na regra, tente esta versão mais simples:

### Versão Simplificada (sem depender de RewriteBase)

**Substitua a seção de regras (linhas 25-47) por:**

```apache
# Regra para URL raiz - Versão simplificada
# Se a URL for exatamente /administrativo/ ou /administrativo, redirecionar
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule .* index.php?url=dashboard [QSA,L]

# Não reescrever se for arquivo ou diretório físico
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Permitir acesso direto à pasta public (assets estáticos)
RewriteCond %{REQUEST_URI} !^/administrativo/public/

# Permitir acesso direto à pasta scripts
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/.*\.php$

# Redirecionar o resto para index.php
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

**Diferença:** Usa `.*` em vez de `^$`, o que pode funcionar melhor com `RewriteBase`.

---

## Solução Alternativa 2: Usar DirectoryIndex

Outra abordagem é usar `DirectoryIndex` para redirecionar a URL raiz:

**Adicione após `RewriteEngine On`:**

```apache
# Se acessar apenas /administrativo/, redirecionar para dashboard
DirectoryIndex index.php?url=dashboard
```

Mas isso pode não funcionar se houver outras configurações.

---

## Solução Alternativa 3: Regra Absoluta (sem RewriteBase)

Remova o `RewriteBase` e use caminhos absolutos:

**Substitua:**

```apache
RewriteEngine On
RewriteBase /administrativo/
```

**Por:**

```apache
RewriteEngine On
# Remover RewriteBase e usar caminhos absolutos nas regras
```

E ajuste as regras para usar caminhos absolutos.

---

## Próximos Passos Recomendados

1. **Execute o Teste 1** (verificar index.php)
2. **Execute o Teste 2** (verificar se .htaccess está sendo processado)
3. **Se necessário, tente a Versão Simplificada** da regra
4. **Verifique os logs** do Apache para erros específicos

---

## Comandos para Executar Agora

```bash
# 1. Verificar index.php
ls -la index.php

# 2. Testar acesso direto ao index.php
curl -I "https://administrativotiaraju.kinghost.net/administrativo/index.php?url=dashboard"

# 3. Verificar permissões do .htaccess
ls -la .htaccess

# 4. Verificar se há erros no .htaccess (sintaxe)
apache2ctl configtest
# Ou
httpd -t
```

---

## Possível Causa: RewriteBase

O `RewriteBase /administrativo/` pode estar causando problemas. Com ele, o Apache remove o base path antes de aplicar o padrão, o que pode fazer com que `^$` não funcione como esperado.

**Solução:** Tente remover o `RewriteBase` e ajustar as regras para usar caminhos relativos ou absolutos.

