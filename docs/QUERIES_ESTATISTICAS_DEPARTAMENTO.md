# Queries SQL - Estatísticas por Departamento

## Query 1: Status dinâmicos por departamento (busca diretamente de tu.status)

Esta query busca os status diretamente da coluna `tu.status` no banco de dados, agrupando por departamento. Usa a mesma lógica de `getSummaryAll()`.

```sql
SELECT 
    d.id as department_id,
    d.name as department_name,
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = 'Ativo'
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
GROUP BY d.id, d.name
```

**Filtros aplicados:**
- `u.status = 'Ativo'` - Apenas usuários ativos
- `t.ativo = 1` - Apenas treinamentos ativos
- Conta diretamente de `tu.status` (não calcula dinamicamente)

**Observação:** Esta query busca os status diretamente da coluna `tu.status` no banco de dados, seguindo a mesma lógica de `getSummaryAll()`.

## Query 2: Contar concluídos por departamento

```sql
SELECT 
    d.id as department_id,
    COUNT(*) as concluidos
FROM adms_training_users tu
LEFT JOIN adms_users u ON u.id = tu.adms_user_id
LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
LEFT JOIN adms_departments d ON u.user_department_id = d.id
WHERE tu.status = 'concluido'
  AND u.id IS NOT NULL
  AND t.id IS NOT NULL
  AND d.id IS NOT NULL
GROUP BY d.id
```

**Observações:**
- Usa `LEFT JOIN` para incluir todos os registros não-órfãos
- Filtra apenas registros com `tu.status = 'concluido'` (busca diretamente do banco)
- Agrupa por departamento

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

**Observação:** 
- `getSummaryAll()` e `getDepartmentStatistics()` agora usam a mesma abordagem: contam diretamente de `tu.status`
- A única diferença é que `getDepartmentStatistics()` agrupa por departamento
- Para garantir que os status estejam atualizados, execute `updateDynamicStatuses()` periodicamente
