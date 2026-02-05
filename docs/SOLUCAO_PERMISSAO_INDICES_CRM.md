# Solução para Erro de Permissão ao Criar Índices

## ❌ Erro Encontrado

```
#1142 INDEX command denied to user 'tiaraju004_add1'@'10.19.0.12' for table 'crm_partners'
```

Este erro indica que o usuário do banco de dados não tem permissão para usar `CREATE INDEX`.

---

## ✅ Solução: Usar ALTER TABLE

Alguns provedores de hospedagem permitem `ALTER TABLE` mas não `CREATE INDEX`. Use o script alternativo:

**Arquivo:** `scripts/add_performance_indexes_crm_hierarchy_ALTER_TABLE.sql`

### Como Aplicar:

1. Acesse o phpMyAdmin
2. Selecione o banco `tiaraju04` (ou `administrativo`)
3. Clique na aba **SQL**
4. Abra o arquivo: `scripts/add_performance_indexes_crm_hierarchy_ALTER_TABLE.sql`
5. Copie todo o conteúdo
6. Cole no campo SQL do phpMyAdmin
7. Clique em **Executar**

---

## 🔧 Se ALTER TABLE Também Não Funcionar

Se você receber erro de permissão mesmo com `ALTER TABLE`, você tem duas opções:

### Opção 1: Contatar Suporte do Hosting

Entre em contato com o suporte do KingHost e solicite:

> "Preciso que o usuário 'tiaraju004_add1' tenha permissão para criar índices nas tabelas do banco 'tiaraju04' para melhorar a performance do sistema."

### Opção 2: Aplicar Índices Manualmente (Um por Vez)

Execute um comando por vez no phpMyAdmin e ignore os erros de índices que já existem:

```sql
-- Execute um por vez, copiando e colando no phpMyAdmin

ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_responsible_user` (`responsible_user_id`);

ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_status` (`status`);

-- Continue com os outros...
```

---

## 📋 Índices Críticos (Prioridade)

Se você só puder criar alguns índices, priorize estes:

### 1. Hierarquia (MAIS IMPORTANTE):
```sql
ALTER TABLE `adms_users` 
ADD INDEX `idx_users_immediate_supervisor` (`immediate_supervisor_id`);
```

### 2. CRM - Parceiros:
```sql
ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_responsible_user` (`responsible_user_id`);
```

### 3. CRM - Oportunidades:
```sql
ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_stage` (`stage_id`);

ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_responsible` (`responsible_user_id`);
```

### 4. CRM - Tags:
```sql
ALTER TABLE `crm_partner_tags` 
ADD INDEX `idx_crm_partner_tags_partner` (`partner_id`);
```

---

## ✅ Verificar se os Índices Foram Criados

Após executar, verifique:

```sql
-- Ver índices da tabela crm_partners
SHOW INDEXES FROM crm_partners;

-- Ver índices da tabela adms_users
SHOW INDEXES FROM adms_users;

-- Ver todos os índices criados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'tiaraju04'
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;
```

---

## 📝 Nota

Os índices melhoram significativamente a performance, mas o sistema funcionará mesmo sem eles. As otimizações de código (N+1 resolvido, cache) já trazem grandes melhorias mesmo sem os índices.

---

**Última atualização:** 2025-02-05

