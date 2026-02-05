# 🔧 Solução Completa: Importação do Banco de Dados

## 🔴 **Problema**

Ao importar o banco de dados, ocorre o erro:
```
#1071 - Specified key was too long; max key length is 767 bytes
```

**Causa:** Colunas VARCHAR(255) com charset utf8mb4 (4 bytes por caractere) resultam em 255 * 4 = 1020 bytes, que excede o limite de 767 bytes para índices únicos no MySQL.

---

## ✅ **Solução: Modificar o SQL ANTES de Importar**

### **Passo 1: Exportar do Servidor Antigo**

1. Acesse phpMyAdmin do servidor antigo
2. Selecione o banco `administrativo`
3. Vá em **Exportar**
4. Escolha **Personalizada**
5. Marque **"Adicionar DROP TABLE"** (opcional)
6. Clique em **Executar**
7. Salve o arquivo `.sql`

---

### **Passo 2: Editar o Arquivo SQL**

Abra o arquivo `.sql` em um editor de texto (Notepad++, VS Code, etc.) e faça as seguintes substituições:

#### **Substituição 1: adms_access_levels**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 2: adms_departments**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 3: adms_positions**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 4: adms_payment_method**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 5: adms_frequency**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 6: adms_cost_center**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

#### **Substituição 7: adms_accounts_plan (TEM 2 ÍNDICES)**
```sql
# PROCURAR:
ADD UNIQUE KEY `idx_unique_name` (`name`), ADD UNIQUE KEY `idx_unique_account` (`account`)

# SUBSTITUIR POR:
ADD UNIQUE KEY `idx_unique_name` (`name`(191)), ADD UNIQUE KEY `idx_unique_account` (`account`(191))
```

---

### **Passo 3: Usar Busca e Substituição Global (Mais Rápido)**

No editor de texto, use **Buscar e Substituir** (Ctrl+H):

**Buscar:**
```sql
ADD UNIQUE KEY `idx_unique_name` (`name`)
```

**Substituir por:**
```sql
ADD UNIQUE KEY `idx_unique_name` (`name`(191))
```

**Buscar:**
```sql
ADD UNIQUE KEY `idx_unique_account` (`account`)
```

**Substituir por:**
```sql
ADD UNIQUE KEY `idx_unique_account` (`account`(191))
```

⚠️ **ATENÇÃO:** Faça isso **ANTES** de importar o SQL!

---

### **Passo 4: Importar no Servidor Novo**

1. Acesse phpMyAdmin do servidor novo
2. Selecione o banco `tiaraju04`
3. Vá em **Importar**
4. Escolha o arquivo `.sql` **EDITADO**
5. Clique em **Executar**

---

## ✅ **Solução Alternativa: Corrigir DEPOIS de Importar**

Se já importou e deu erro, execute este script SQL no phpMyAdmin:

```sql
-- Corrigir adms_access_levels
ALTER TABLE `adms_access_levels` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_departments
ALTER TABLE `adms_departments` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_positions
ALTER TABLE `adms_positions` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_payment_method
ALTER TABLE `adms_payment_method` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_frequency
ALTER TABLE `adms_frequency` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_cost_center
ALTER TABLE `adms_cost_center` 
DROP INDEX IF EXISTS `idx_unique_name`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191));

-- Corrigir adms_accounts_plan (tem 2 índices)
ALTER TABLE `adms_accounts_plan` 
DROP INDEX IF EXISTS `idx_unique_name`,
DROP INDEX IF EXISTS `idx_unique_account`,
ADD UNIQUE KEY `idx_unique_name` (`name`(191)),
ADD UNIQUE KEY `idx_unique_account` (`account`(191));
```

**OU** use o arquivo `scripts/fix_indices_mysql.sql` que já contém todos os comandos.

---

## 🚀 **Solução Mais Rápida: Usar o Script**

1. **Baixe o arquivo:** `scripts/fix_indices_mysql.sql`
2. **No phpMyAdmin do servidor novo:**
   - Selecione o banco `tiaraju04`
   - Vá em **SQL**
   - Cole o conteúdo do arquivo `fix_indices_mysql.sql`
   - Clique em **Executar**

---

## 📋 **Checklist de Importação**

- [ ] Exportar banco do servidor antigo
- [ ] Editar SQL (substituir índices)
- [ ] Limpar todas as tabelas no servidor novo (se necessário)
- [ ] Importar SQL editado
- [ ] Se der erro, executar script de correção
- [ ] Verificar se todas as tabelas foram criadas
- [ ] Testar conexão do sistema

---

## ⚠️ **Importante**

- **191 caracteres** é suficiente para a maioria dos nomes
- O prefixo `(191)` no índice **não limita** o tamanho da coluna, apenas o que é indexado
- Se precisar de mais caracteres, use **TEXT** com índice FULLTEXT (mas sem UNIQUE)

---

## 🔗 **Arquivos Relacionados**

- `scripts/fix_indices_mysql.sql` - Script SQL completo para correção
- `docs/CORRECAO_ERRO_INDICE_MYSQL.md` - Documentação detalhada do problema

