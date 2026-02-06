# 🔧 Resolver Erro: Duplicate Entry ao Importar Backup

## ❌ Erro Encontrado

```
#1062 - Duplicate entry '1' for key 'PRIMARY'
```

**Causa:** O backup está tentando inserir registros com IDs que já existem nas tabelas (criados pelas seeds ou migrations).

## ✅ Soluções

### Solução 1: Limpar Tabelas Antes de Importar (Recomendado)

**Opção A: Limpar todas as tabelas (se não tem dados importantes):**

```sql
-- No phpMyAdmin, execute este SQL antes de importar
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE adms_access_levels;
TRUNCATE TABLE adms_departments;
TRUNCATE TABLE adms_positions;
-- ... (adicione outras tabelas conforme necessário)

SET FOREIGN_KEY_CHECKS = 1;
```

**Opção B: Limpar apenas tabelas específicas:**

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- Limpar apenas as tabelas que estão dando erro
TRUNCATE TABLE adms_access_levels;

SET FOREIGN_KEY_CHECKS = 1;
```

### Solução 2: Modificar o Backup para Usar INSERT IGNORE

1. **Abra o arquivo SQL do backup** em um editor de texto
2. **Substitua** todos os `INSERT INTO` por `INSERT IGNORE INTO`
3. **Salve** e importe novamente

**Exemplo:**
```sql
-- ANTES
INSERT INTO `adms_access_levels` (`id`, `name`, ...) VALUES (1, 'Super Administrador', ...);

-- DEPOIS
INSERT IGNORE INTO `adms_access_levels` (`id`, `name`, ...) VALUES (1, 'Super Administrador', ...);
```

**No Windows (PowerShell):**
```powershell
(Get-Content backup.sql) -replace 'INSERT INTO', 'INSERT IGNORE INTO' | Set-Content backup_ignore.sql
```

**No Linux/Mac:**
```bash
sed 's/INSERT INTO/INSERT IGNORE INTO/g' backup.sql > backup_ignore.sql
```

### Solução 3: Modificar o Backup para Usar REPLACE INTO

Similar à Solução 2, mas substitui registros existentes:

```sql
-- Substituir
INSERT INTO → REPLACE INTO
```

**No Windows (PowerShell):**
```powershell
(Get-Content backup.sql) -replace 'INSERT INTO', 'REPLACE INTO' | Set-Content backup_replace.sql
```

### Solução 4: Remover IDs do Backup (Deixar AUTO_INCREMENT Gerar)

1. **Abra o arquivo SQL do backup**
2. **Remova os IDs** dos INSERTs
3. **Deixe o AUTO_INCREMENT** gerar novos IDs

**Exemplo:**
```sql
-- ANTES
INSERT INTO `adms_access_levels` (`id`, `name`, `create_at`, `update_at`) 
VALUES (1, 'Super Administrador', '2015-09-11 10:53:20', NULL);

-- DEPOIS (sem o ID)
INSERT INTO `adms_access_levels` (`name`, `create_at`, `update_at`) 
VALUES ('Super Administrador', '2015-09-11 10:53:20', NULL);
```

**⚠️ Cuidado:** Isso pode quebrar relacionamentos (foreign keys) se outras tabelas referenciam esses IDs.

### Solução 5: Exportar Apenas Tabelas Vazias ou Específicas

Se você só precisa de algumas tabelas:

1. No phpMyAdmin, exporte apenas as tabelas que **não** foram populadas pelas seeds
2. Ou exporte apenas as tabelas que você sabe que estão vazias

## 🎯 Solução Recomendada (Passo a Passo)

### Para o seu caso específico:

1. **Limpar as tabelas que estão dando erro:**
   ```sql
   SET FOREIGN_KEY_CHECKS = 0;
   TRUNCATE TABLE adms_access_levels;
   TRUNCATE TABLE adms_departments;
   TRUNCATE TABLE adms_positions;
   -- Adicione outras tabelas que foram populadas pelas seeds
   SET FOREIGN_KEY_CHECKS = 1;
   ```

2. **Importar o backup novamente:**
   - No phpMyAdmin, vá em **Importar**
   - Selecione o arquivo SQL
   - Clique em **Executar**

### Alternativa Rápida (INSERT IGNORE):

1. **Modificar o backup:**
   ```powershell
   # No PowerShell (Windows)
   cd C:\caminho\para\backup
   (Get-Content backup.sql) -replace 'INSERT INTO', 'INSERT IGNORE INTO' | Set-Content backup_ignore.sql
   ```

2. **Importar o arquivo modificado:**
   - Importe `backup_ignore.sql` em vez de `backup.sql`

## 📋 Checklist

- [ ] Identificar quais tabelas estão dando erro
- [ ] Escolher uma solução (recomendado: Limpar + Importar)
- [ ] Fazer backup antes de limpar (se necessário)
- [ ] Limpar tabelas problemáticas
- [ ] Importar backup novamente
- [ ] Verificar se os dados foram importados corretamente

## ⚠️ Importante

1. **Sempre faça backup antes de limpar tabelas:**
   ```sql
   -- Exportar tabelas antes de limpar
   mysqldump -h mysql.tiaraju.com.br -u tiaraju004_add1 -p tiaraju04 adms_access_levels > backup_antes_limpar.sql
   ```

2. **Verifique se as seeds não criaram dados importantes:**
   - Se as seeds criaram dados que você precisa manter, use `INSERT IGNORE` em vez de limpar

3. **Para produção, prefira `INSERT IGNORE`** em vez de limpar, para evitar perda de dados

## 🔍 Identificar Todas as Tabelas com Dados das Seeds

Execute este SQL para ver quais tabelas têm dados:

```sql
SELECT 
    TABLE_NAME,
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'tiaraju04'
AND TABLE_ROWS > 0
ORDER BY TABLE_NAME;
```

Isso mostra todas as tabelas que têm registros e podem causar conflito.

