# Como Verificar Índices no Banco de Dados

## 📋 Comandos SQL para Verificar Índices

### 1. **Ver Índices de uma Tabela Específica**

```sql
-- Ver todos os índices da tabela crm_partners
SHOW INDEXES FROM `crm_partners`;

-- Ver todos os índices da tabela adms_users
SHOW INDEXES FROM `adms_users`;
```

### 2. **Ver Todos os Índices do Banco (Visão Geral)**

```sql
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Colunas',
    NON_UNIQUE as 'Não Único',
    INDEX_TYPE as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
ORDER BY TABLE_NAME, INDEX_NAME;
```

### 3. **Verificar Índices Específicos que Criamos**

```sql
-- Verificar índices de CRM
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('crm_partners', 'crm_partner_tags', 'crm_opportunities')
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Verificar índices de hierarquia
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'adms_users'
AND INDEX_NAME IN ('idx_users_immediate_supervisor', 'idx_users_status_supervisor')
ORDER BY INDEX_NAME;
```

### 4. **Contar Índices por Tabela**

```sql
SELECT 
    TABLE_NAME as 'Tabela',
    COUNT(DISTINCT INDEX_NAME) as 'Total de Índices'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('crm_partners', 'crm_partner_tags', 'crm_opportunities', 'adms_users')
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;
```

---

## 🔍 Script Completo de Verificação

**Arquivo:** `scripts/verificar_indices.sql`

Este script contém todos os comandos acima e mais alguns para verificar quais índices estão faltando.

**Como usar:**
1. Acesse phpMyAdmin
2. Selecione o banco `tiaraju04` (ou `administrativo`)
3. Aba **SQL**
4. Execute o conteúdo de `scripts/verificar_indices.sql`

---

## ✅ Índices Esperados

### Tabela: `crm_partners`
- ✅ `idx_crm_partners_responsible_user`
- ✅ `idx_crm_partners_status`
- ✅ `idx_crm_partners_department`

### Tabela: `crm_partner_tags`
- ✅ `idx_crm_partner_tags_partner`
- ✅ `idx_crm_partner_tags_tag`
- ✅ `idx_crm_partner_tags_composite`

### Tabela: `crm_opportunities`
- ✅ `idx_crm_opportunities_stage`
- ✅ `idx_crm_opportunities_responsible`
- ✅ `idx_crm_opportunities_status`
- ✅ `idx_crm_opportunities_stage_status`
- ✅ `idx_crm_opportunities_partner`

### Tabela: `adms_users`
- ✅ `idx_users_immediate_supervisor` (CRÍTICO)
- ✅ `idx_users_status_supervisor`

**Total:** 13 índices

---

## 📝 Nota

Se alguns índices não puderem ser criados por falta de permissão, o sistema ainda funcionará. As otimizações de código (N+1 resolvido, cache) já trazem grandes melhorias mesmo sem todos os índices.

---

**Última atualização:** 2025-02-05

