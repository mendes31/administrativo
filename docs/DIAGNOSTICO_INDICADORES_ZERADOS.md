# Diagnóstico: Por que os Indicadores Estão Zerados?

## Problema Identificado

Os indicadores "A Fazer (Dentro do Prazo)" e "Pendentes (Próx. Vencimento)" estão zerados na tabela "Estatísticas por Departamento", mesmo após executar o script de atualização.

## Análise das Queries

### Query Atual em `getDepartmentStatistics()`

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
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
GROUP BY d.id, d.name
```

### Query de `getSummaryAll()` (que funciona)

```sql
SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia_count,
    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as proximo_vencimento_count,
    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencido_count,
    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendado_count
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
```

**Diferença:** A única diferença é o `INNER JOIN` com `adms_departments` e o `GROUP BY`.

## Queries de Diagnóstico

### 1. Verificar quais status existem no banco (geral)

```sql
SELECT 
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
GROUP BY tu.status
ORDER BY total DESC;
```

### 2. Verificar status por departamento (detalhado)

```sql
SELECT 
    d.id AS department_id,
    d.name AS department_name,
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
INNER JOIN adms_departments d ON u.user_department_id = d.id
GROUP BY d.id, d.name, tu.status
ORDER BY d.name, tu.status;
```

### 3. Comparar contagem total vs. por departamento

```sql
-- Total geral (como getSummaryAll faz)
SELECT 
    COUNT(*) as total_entries,
    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1;

-- Total por departamento (soma de todos os departamentos)
SELECT 
    SUM(total_entries) as total_entries,
    SUM(em_dia) as em_dia,
    SUM(pendentes) as pendentes,
    SUM(vencidos) as vencidos,
    SUM(agendados) as agendados
FROM (
    SELECT 
        d.id as department_id,
        COUNT(*) as total_entries,
        SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
        SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
        SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
    FROM adms_training_users tu
    INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
    INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
    INNER JOIN adms_departments d ON u.user_department_id = d.id
    GROUP BY d.id
) as dept_stats;
```

### 4. Verificar se há registros sem departamento

```sql
SELECT 
    COUNT(*) as registros_sem_departamento
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
WHERE u.user_department_id IS NULL;
```

## Possíveis Causas

1. **Status não atualizados no banco**: Mesmo após executar o script, os status podem não ter sido atualizados corretamente
2. **Registros sem departamento**: Se houver registros com `user_department_id IS NULL`, eles não aparecerão na query
3. **Status diferentes do esperado**: Pode haver status como `'pendente'` ou outros valores que não estão sendo contados
4. **Problema no JOIN**: O `INNER JOIN` com `adms_departments` pode estar excluindo registros

## Solução

Execute as queries de diagnóstico acima e compare os resultados. Se os status existirem no banco mas não aparecerem na query agrupada, o problema está no JOIN ou na lógica de agrupamento.

