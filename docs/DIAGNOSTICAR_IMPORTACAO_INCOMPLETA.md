# 🔍 Diagnosticar Importação Incompleta

## ❓ Por que não importou todos os registros?

O `INSERT IGNORE` pode ignorar registros por várias razões. Vamos diagnosticar:

## 🔍 Possíveis Causas

### 1. ✅ Registros Já Existem (Comportamento Esperado)

**Causa:** `INSERT IGNORE` ignora registros com chave primária duplicada.

**Verificar:**
```sql
-- Ver quantos registros existem agora
SELECT COUNT(*) as total_atual FROM adms_access_levels;

-- Ver quantos registros o backup tentou inserir
-- (conte os INSERT IGNORE no arquivo SQL)
```

**Solução:** Isso é **normal** se os registros já existiam (das seeds ou importação anterior).

### 2. ❌ Erros Silenciosos (INSERT IGNORE Ignora Tudo)

**Causa:** `INSERT IGNORE` ignora **TODOS** os erros, não só duplicatas:
- Foreign key violations
- Constraint violations
- Valores inválidos
- Tipos incompatíveis

**Verificar:**
```sql
-- Verificar se há registros órfãos (sem foreign keys válidas)
SELECT * FROM adms_users WHERE user_department_id NOT IN (SELECT id FROM adms_departments);
```

**Solução:** Usar `INSERT` normal e ver os erros, ou verificar logs do MySQL.

### 3. ❌ Foreign Keys Quebradas

**Causa:** Registros que dependem de outros que não existem.

**Exemplo:**
```sql
-- Se o backup tenta inserir:
INSERT IGNORE INTO adms_users (id, user_department_id) VALUES (10, 999);
-- Mas adms_departments não tem ID 999 → Ignorado silenciosamente
```

**Verificar:**
```sql
-- Verificar foreign keys quebradas
SELECT 
    u.id,
    u.name,
    u.user_department_id,
    d.id as dept_exists
FROM adms_users u
LEFT JOIN adms_departments d ON u.user_department_id = d.id
WHERE u.user_departments_id IS NOT NULL AND d.id IS NULL;
```

**Solução:** Importar tabelas na ordem correta (pais antes de filhos).

### 4. ❌ Valores NULL em Campos NOT NULL

**Causa:** Backup tem NULL em campos obrigatórios.

**Verificar:**
```sql
-- Ver estrutura da tabela
DESCRIBE adms_users;

-- Ver se há registros com NULL em campos NOT NULL
SELECT * FROM adms_users WHERE name IS NULL OR email IS NULL;
```

**Solução:** Corrigir o backup ou ajustar a estrutura da tabela.

### 5. ❌ Timeout ou Limite de Tamanho

**Causa:** Arquivo muito grande ou timeout do PHP/MySQL.

**Verificar:**
- Tamanho do arquivo SQL
- Limites do phpMyAdmin (`upload_max_filesize`, `post_max_size`)
- Timeout do MySQL (`max_execution_time`)

**Solução:** Aumentar limites ou dividir o arquivo.

### 6. ❌ Encoding/Charset Incompatível

**Causa:** Caracteres especiais corrompidos.

**Verificar:**
```sql
-- Ver charset da tabela
SHOW CREATE TABLE adms_users;

-- Ver se há caracteres estranhos
SELECT * FROM adms_users WHERE name LIKE '%?%' OR name LIKE '%%';
```

**Solução:** Exportar/importar com charset correto (utf8mb4).

## 🔧 Scripts de Diagnóstico

### Script 1: Comparar Quantidade de Registros

```sql
-- Execute no phpMyAdmin para ver quantos registros cada tabela deveria ter
-- Compare com o que foi importado

SELECT 
    'adms_access_levels' as tabela,
    COUNT(*) as registros_importados,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'adms_access_levels') as tabela_existe
FROM adms_access_levels
UNION ALL
SELECT 
    'adms_users',
    COUNT(*),
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'adms_users')
FROM adms_users;
-- ... (adicione outras tabelas)
```

### Script 2: Verificar Foreign Keys Quebradas

```sql
-- Verificar todas as foreign keys quebradas
SELECT 
    'adms_users' as tabela,
    COUNT(*) as registros_orfos
FROM adms_users u
LEFT JOIN adms_departments d ON u.user_department_id = d.id
WHERE u.user_department_id IS NOT NULL AND d.id IS NULL;
```

### Script 3: Verificar Constraints Violadas

```sql
-- Verificar se há valores duplicados em campos únicos (sem índice único)
SELECT name, COUNT(*) as duplicatas
FROM adms_access_levels
GROUP BY name
HAVING COUNT(*) > 1;
```

## 🛠️ Soluções

### Solução 1: Ver Erros Reais (Sem INSERT IGNORE)

1. **Criar backup de teste sem INSERT IGNORE:**
   ```bash
   # Copiar backup original
   cp backup.sql backup_teste.sql
   ```

2. **Importar e ver os erros:**
   - Importe `backup_teste.sql` no phpMyAdmin
   - Anote todos os erros que aparecerem
   - Isso mostra o que está sendo ignorado

3. **Corrigir os problemas identificados**

### Solução 2: Importar em Partes

1. **Dividir o backup por tabelas:**
   ```bash
   # Extrair apenas uma tabela do backup
   grep -A 1000 "INSERT INTO \`adms_users\`" backup.sql > backup_adms_users.sql
   ```

2. **Importar tabela por tabela:**
   - Importe cada tabela separadamente
   - Veja quais dão erro
   - Corrija antes de continuar

### Solução 3: Usar INSERT com ON DUPLICATE KEY UPDATE

Se você quer **atualizar** registros existentes em vez de ignorar:

```sql
-- Substituir no backup
INSERT INTO → INSERT INTO ... ON DUPLICATE KEY UPDATE id=id
```

**Script para corrigir:**
```php
// Adicionar ON DUPLICATE KEY UPDATE após cada INSERT
$conteudo = preg_replace(
    '/INSERT IGNORE INTO `([^`]+)`[^;]+;/',
    '$0 ON DUPLICATE KEY UPDATE id=id',
    $conteudo
);
```

### Solução 4: Verificar Logs do MySQL

```sql
-- Ver últimos erros do MySQL
SHOW WARNINGS;
SHOW ERRORS;
```

Ou verificar logs do servidor:
```bash
# No servidor
tail -f /var/log/mysql/error.log
```

## 📊 Checklist de Diagnóstico

- [ ] Verificar quantos registros foram importados vs. esperados
- [ ] Verificar se há foreign keys quebradas
- [ ] Verificar se há constraints violadas
- [ ] Verificar se há valores NULL em campos NOT NULL
- [ ] Verificar logs do MySQL (SHOW WARNINGS)
- [ ] Testar importação sem INSERT IGNORE para ver erros
- [ ] Verificar tamanho do arquivo e limites do PHP
- [ ] Verificar encoding/charset

## 🎯 Próximos Passos

1. **Execute os scripts de diagnóstico acima**
2. **Identifique qual é a causa principal**
3. **Aplique a solução correspondente**
4. **Re-importe se necessário**

## 💡 Dica: Importação Mais Segura

Para evitar problemas, importe na ordem correta:

1. Tabelas **pais** (sem dependências)
2. Tabelas **filhas** (com foreign keys)

**Ordem sugerida:**
```
1. adms_departments
2. adms_positions
3. adms_access_levels
4. adms_users (depende de departments, positions)
5. ... (outras tabelas)
```

