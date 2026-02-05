# Solução: Erro Composer Vendor em Produção

## 🔍 Problema Identificado

**Erro exibido:**
```
Fatal error: Uncaught Error: Failed opening required 
'/home/tiaraju/www/administrativo/vendor/composer/ClassLoader.php'
```

**Causa:** O diretório `vendor` está incompleto ou corrompido. Os arquivos do Composer não foram instalados corretamente no servidor.

---

## ✅ Solução: Executar Composer no Servidor

### Passo 1: Acessar o Servidor via Putty

Conecte-se ao servidor via Putty e navegue até o diretório do projeto:

```bash
cd /home/tiaraju/www/administrativo
```

---

### Passo 2: Verificar se composer.phar existe

```bash
ls -la composer.phar
```

**Se não existir**, baixe o Composer:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

---

### Passo 3: Verificar Versão PHP

```bash
php -v
```

**Importante:** O Composer deve ser executado com a mesma versão PHP que o servidor web usa.

---

### Passo 4: Executar Composer Install

Execute o comando para instalar todas as dependências:

```bash
php composer.phar install --no-dev --optimize-autoloader
```

**Parâmetros:**
- `--no-dev`: Não instala dependências de desenvolvimento (recomendado para produção)
- `--optimize-autoloader`: Otimiza o autoloader para melhor performance

---

### Passo 5: Verificar se funcionou

```bash
# Verificar se o arquivo ClassLoader.php foi criado
ls -la vendor/composer/ClassLoader.php

# Verificar se o autoload.php existe
ls -la vendor/autoload.php
```

---

## 🔄 Alternativa: Se composer install não funcionar

### Opção 1: Limpar e Reinstalar

```bash
# Remover diretório vendor (cuidado!)
rm -rf vendor/

# Reinstalar tudo
php composer.phar install --no-dev --optimize-autoloader
```

---

### Opção 2: Apenas Regenerar Autoloader

Se as dependências já estão instaladas, apenas regenere o autoloader:

```bash
php composer.phar dump-autoload --optimize
```

---

## ⚠️ Problemas Comuns e Soluções

### Problema 1: "composer: command not found"

**Solução:** Use `php composer.phar` em vez de apenas `composer`:

```bash
php composer.phar install --no-dev --optimize-autoloader
```

---

### Problema 2: "Your Composer dependencies require a PHP version >= 8.2.0"

**Causa:** As dependências requerem PHP 8.2+, mas o servidor tem PHP 7.4.

**Solução:** Ajustar `composer.json` para aceitar PHP 7.4 (ver documento `ANALISE_COMPATIBILIDADE_PHP74.md`).

---

### Problema 3: "Memory limit exhausted"

**Solução:** Aumentar limite de memória temporariamente:

```bash
php -d memory_limit=512M composer.phar install --no-dev --optimize-autoloader
```

---

### Problema 4: Permissões negadas

**Solução:** Verificar e corrigir permissões:

```bash
# Verificar permissões do diretório
ls -la vendor/

# Corrigir permissões (se necessário)
chmod -R 755 vendor/
```

---

## 📋 Comandos Completos (Copiar e Colar)

Execute estes comandos **na ordem** no Putty:

```bash
# 1. Navegar para o diretório
cd /home/tiaraju/www/administrativo

# 2. Verificar versão PHP
php -v

# 3. Verificar se composer.phar existe
ls -la composer.phar

# 4. Se não existir, baixar Composer
# php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
# php composer-setup.php
# php -r "unlink('composer-setup.php');"

# 5. Executar Composer Install
php composer.phar install --no-dev --optimize-autoloader

# 6. Verificar se funcionou
ls -la vendor/composer/ClassLoader.php
ls -la vendor/autoload.php

# 7. Se ainda não funcionar, limpar e reinstalar
# rm -rf vendor/
# php composer.phar install --no-dev --optimize-autoloader
```

---

## 🔍 Verificação Pós-Instalação

Após executar o Composer, verifique:

1. **Arquivo ClassLoader.php existe:**
   ```bash
   ls -la vendor/composer/ClassLoader.php
   ```

2. **Autoload.php existe:**
   ```bash
   ls -la vendor/autoload.php
   ```

3. **Diretório vendor está completo:**
   ```bash
   ls -la vendor/ | head -20
   ```

4. **Testar acesso ao site:**
   - Acesse `https://tiaraju.com.br/administrativo/` no navegador
   - O erro deve desaparecer

---

## 💡 Dica: Adicionar ao .gitignore

Certifique-se de que `vendor/` está no `.gitignore` (não deve ser versionado):

```bash
# Verificar .gitignore
grep "vendor" .gitignore
```

Se não estiver, adicione:
```
vendor/
```

---

## 📞 Se o Problema Persistir

Se após executar todos os comandos o erro continuar:

1. **Verificar logs:**
   ```bash
   tail -n 50 app/logs/*.log
   ```

2. **Verificar permissões:**
   ```bash
   ls -la vendor/
   chmod -R 755 vendor/
   ```

3. **Verificar espaço em disco:**
   ```bash
   df -h
   ```

4. **Verificar versão PHP vs Composer:**
   ```bash
   php -v
   php composer.phar --version
   ```

---

## ✅ Checklist Final

- [ ] Acessei o servidor via Putty
- [ ] Naveguei para `/home/tiaraju/www/administrativo`
- [ ] Verifiquei que `composer.phar` existe
- [ ] Executei `php composer.phar install --no-dev --optimize-autoloader`
- [ ] Verifiquei que `vendor/composer/ClassLoader.php` foi criado
- [ ] Testei o acesso ao site no navegador
- [ ] O erro desapareceu

---

## 🎯 Resumo

**O que fazer:**
1. Acessar servidor via Putty
2. Navegar para o diretório do projeto
3. Executar: `php composer.phar install --no-dev --optimize-autoloader`
4. Verificar se os arquivos foram criados
5. Testar o site

**Tempo estimado:** 2-5 minutos


