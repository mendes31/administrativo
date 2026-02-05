# 🔧 Correção: Erro #1071 - Specified key was too long

## 🔴 **Problema**

Ao importar o banco de dados, ocorre o erro:
```
#1071 - Specified key was too long; max key length is 767 bytes
```

**Causa:** A coluna `name` da tabela `adms_access_levels` é VARCHAR(255) com charset utf8mb4 (4 bytes por caractere), resultando em 255 * 4 = 1020 bytes, que excede o limite de 767 bytes para índices únicos no MySQL.

---

## ✅ **Solução 1: Modificar o SQL de Importação (Recomendado)**

Antes de importar, **substitua** esta linha no SQL:

**❌ ANTES (causa erro):**
```sql
ALTER TABLE `adms_access_levels`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_unique_name` (`name`);
```

**✅ DEPOIS (corrigido):**
```sql
ALTER TABLE `adms_access_levels`
ADD PRIMARY KEY (`id`), 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));
```

O `(191)` limita o índice aos primeiros 191 caracteres: 191 * 4 = 764 bytes < 767 bytes ✅

---

## ✅ **Solução 2: Limitar o Tamanho da Coluna**

Se preferir, altere a coluna antes de criar o índice:

```sql
-- Alterar coluna para VARCHAR(191)
ALTER TABLE `adms_access_levels` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL;

-- Depois criar o índice normalmente
ALTER TABLE `adms_access_levels`
ADD PRIMARY KEY (`id`), 
ADD UNIQUE KEY `idx_unique_name` (`name`);
```

---

## ✅ **Solução 3: Usar Comando SQL Direto no phpMyAdmin**

No phpMyAdmin, vá em **SQL** e execute:

```sql
-- Se a tabela já foi criada sem o índice
ALTER TABLE `adms_access_levels` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- OU se precisar alterar a coluna primeiro
ALTER TABLE `adms_access_levels` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);
```

---

## 🔍 **Verificar Outras Tabelas com Mesmo Problema**

Este erro pode ocorrer em outras tabelas que também têm índice único em colunas `name` VARCHAR(255):

- `adms_departments`
- `adms_positions`
- `adms_payment_method`
- `adms_frequency`
- `adms_cost_center`
- `adms_accounts_plan`

**Solução para todas:**
```sql
-- Para cada tabela, use prefixo de 191 caracteres no índice
ALTER TABLE `adms_departments` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_positions` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_payment_method` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_frequency` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_cost_center` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_accounts_plan` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));
```

---

## 🚀 **Solução Rápida: Script SQL Completo**

Crie um arquivo `fix_indices.sql` e execute no phpMyAdmin:

```sql
-- Corrigir índice de adms_access_levels
ALTER TABLE `adms_access_levels` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir outras tabelas (se necessário)
ALTER TABLE `adms_departments` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_positions` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_payment_method` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_frequency` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_cost_center` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

ALTER TABLE `adms_accounts_plan` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));
```

---

## 📋 **Passo a Passo no phpMyAdmin**

1. **Exportar do servidor antigo:**
   - Selecione o banco `administrativo`
   - Vá em **Exportar**
   - Escolha **Rápida** ou **Personalizada**
   - Clique em **Executar**

2. **Editar o SQL exportado:**
   - Abra o arquivo `.sql` em um editor de texto
   - Procure por: `ADD UNIQUE KEY \`idx_unique_name\` (\`name\`)`
   - Substitua por: `ADD UNIQUE KEY \`idx_unique_name\` (\`name\`(191))`
   - Salve o arquivo

3. **Importar no servidor novo:**
   - Selecione o banco `tiaraju04`
   - Vá em **Importar**
   - Escolha o arquivo `.sql` editado
   - Clique em **Executar**

---

## ⚠️ **Importante**

- **191 caracteres** é suficiente para a maioria dos nomes
- Se precisar de mais caracteres, use **255** mas sem índice único, ou use **TEXT** com índice FULLTEXT
- O prefixo `(191)` no índice **não limita** o tamanho da coluna, apenas o que é indexado

---

## 🔗 **Referências**

- [MySQL Index Key Length Limits](https://dev.mysql.com/doc/refman/8.0/en/innodb-limits.html)
- [UTF8MB4 Character Set](https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb4.html)

