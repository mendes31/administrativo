# Instalação do Cache de Queries no Servidor

## 📋 O que precisa ser feito

### 1. **Criar pasta de cache (OBRIGATÓRIO)**

O código tenta criar a pasta automaticamente, mas no servidor pode ser necessário criar manualmente com as permissões corretas.

**Via Putty, execute:**

```bash
# Navegar até o diretório do projeto
cd /home/tiaraju02/www/administrativo

# Criar a pasta de cache (se não existir)
mkdir -p storage/cache/queries

# Definir permissões (permite que o PHP escreva arquivos)
chmod 775 storage/cache/queries

# Verificar se foi criada
ls -la storage/cache/
```

**Resultado esperado:**
```
drwxrwxr-x 2 tiaraju02 tiaraju 4096 Feb  5 15:00 queries
```

---

### 2. **Verificar se a pasta já existe**

Se a pasta `storage/cache/queries` já existir (criada automaticamente pelo código), apenas verifique as permissões:

```bash
# Verificar permissões
ls -la storage/cache/queries

# Se as permissões estiverem incorretas, corrigir:
chmod 775 storage/cache/queries
```

---

### 3. **Testar o cache (OPCIONAL)**

Após criar a pasta, você pode testar se o cache está funcionando:

1. Acesse qualquer página que use `getAllTrainingsSelect()`, `getAllUsersSelect()`, etc.
2. Verifique se a pasta foi criada e se há arquivos JSON:

```bash
# Verificar se arquivos de cache foram criados
ls -la storage/cache/queries/

# Exemplo de saída esperada:
# -rw-r--r-- 1 tiaraju02 tiaraju  1234 Feb  5 15:00 trainings_select_all.json
# -rw-r--r-- 1 tiaraju02 tiaraju  5678 Feb  5 15:00 users_select_all.json
```

---

### 4. **Limpar cache manualmente (se necessário)**

Se precisar limpar o cache manualmente:

```bash
# Limpar todo o cache
php scripts/clear_query_cache.php

# Ou limpar apenas cache de treinamentos
php scripts/clear_query_cache.php trainings
```

---

## ⚠️ Problemas Comuns

### Erro: "Permission denied" ao criar cache

**Solução:**
```bash
# Verificar proprietário da pasta
ls -la storage/

# Se necessário, ajustar proprietário
chown -R tiaraju02:tiaraju storage/cache/
chmod -R 775 storage/cache/
```

### Cache não está funcionando

**Verificar:**
1. A pasta existe? `ls -la storage/cache/queries`
2. Tem permissão de escrita? `touch storage/cache/queries/test.txt && rm storage/cache/queries/test.txt`
3. Verificar logs de erro do PHP

---

## ✅ Checklist de Instalação

- [ ] Pasta `storage/cache/queries` criada
- [ ] Permissões corretas (775)
- [ ] Testar acesso a uma página que usa cache
- [ ] Verificar se arquivos JSON estão sendo criados

---

## 📝 Notas Importantes

1. **A pasta é criada automaticamente** - O código PHP tenta criar a pasta automaticamente, mas pode falhar se não tiver permissões. Por isso, é recomendado criar manualmente.

2. **Permissões** - A pasta precisa ter permissão de escrita para o usuário do PHP (geralmente o mesmo usuário do servidor web).

3. **Cache expira automaticamente** - Após 5 minutos, o cache expira e é recriado automaticamente.

4. **Invalidação automática** - O cache é invalidado automaticamente quando há create/update/delete nos repositórios.

---

**Última atualização:** 2025-02-05

