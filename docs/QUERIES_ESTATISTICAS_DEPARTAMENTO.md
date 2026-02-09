# Queries SQL - Estatísticas por Departamento

## Query 1: Buscar dados para cálculo dinâmico de status

Esta query busca todos os registros de treinamentos de usuários ativos e treinamentos ativos, com suas informações necessárias para calcular o status dinamicamente.

```sql
SELECT 
    d.id as department_id,
    d.name as department_name,
    tu.id as training_user_id,
    tu.data_limite_primeiro_treinamento,
    tu.data_agendada,
    tu.tipo_vinculo,
    t.prazo_treinamento,
    ta_last.data_realizacao
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = 'Ativo'
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
LEFT JOIN (
    SELECT 
        ta1.adms_user_id,
        ta1.adms_training_id,
        ta1.data_realizacao
    FROM adms_training_applications ta1
    INNER JOIN (
        SELECT 
            adms_user_id,
            adms_training_id,
            MAX(created_at) as max_created_at
        FROM adms_training_applications
        GROUP BY adms_user_id, adms_training_id
    ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
        AND ta1.adms_training_id = ta2.adms_training_id 
        AND ta1.created_at = ta2.max_created_at
) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
    AND ta_last.adms_training_id = tu.adms_training_id
    AND (ta_last.created_at >= tu.created_at OR ta_last.created_at IS NULL)
```

**Filtros aplicados:**
- `u.status = 'Ativo'` - Apenas usuários ativos
- `t.ativo = 1` - Apenas treinamentos ativos
- Busca última aplicação para verificar se está concluído

**Problema identificado:** Esta query pode estar excluindo registros que deveriam ser contados se o status no banco (`tu.status`) estiver desatualizado.

## Query 2: Contar concluídos por departamento

```sql
SELECT 
    d.id as department_id,
    COUNT(*) as concluidos
FROM adms_training_users tu
LEFT JOIN adms_users u ON u.id = tu.adms_user_id
LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
LEFT JOIN adms_departments d ON u.user_department_id = d.id
LEFT JOIN (
    SELECT 
        ta1.adms_user_id,
        ta1.adms_training_id,
        ta1.data_realizacao
    FROM adms_training_applications ta1
    INNER JOIN (
        SELECT 
            adms_user_id,
            adms_training_id,
            MAX(created_at) as max_created_at
        FROM adms_training_applications
        GROUP BY adms_user_id, adms_training_id
    ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
        AND ta1.adms_training_id = ta2.adms_training_id 
        AND ta1.created_at = ta2.max_created_at
) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
    AND ta_last.adms_training_id = tu.adms_training_id
WHERE ta_last.data_realizacao IS NOT NULL
  AND u.id IS NOT NULL
  AND t.id IS NOT NULL
  AND d.id IS NOT NULL
GROUP BY d.id
```

## Query 3: Buscar todos os departamentos

```sql
SELECT id, name FROM adms_departments ORDER BY name
```

## Comparação com getSummaryAll()

A query de `getSummaryAll()` usa uma abordagem diferente - conta diretamente da coluna `tu.status`:

```sql
SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
    SUM(CASE WHEN tu.status = "agendado" THEN 1 ELSE 0 END) as agendado_count,
    SUM(CASE WHEN tu.status = "proximo_vencimento" THEN 1 ELSE 0 END) as proximo_vencimento_count,
    SUM(CASE WHEN tu.status IN ("em_dia","dentro_do_prazo") THEN 1 ELSE 0 END) as em_dia_count
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = "Ativo"
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
```

**Diferença crítica:** 
- `getSummaryAll()` conta de `tu.status` (pode estar desatualizado)
- `getDepartmentStatistics()` calcula dinamicamente baseado em datas

**Solução:** Devemos usar a mesma abordagem de `getSummaryAll()` mas com agrupamento por departamento, OU garantir que `tu.status` esteja sempre atualizado executando `updateDynamicStatuses()` antes.
