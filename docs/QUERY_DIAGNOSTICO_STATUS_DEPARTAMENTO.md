# Query de Diagnóstico - Status por Departamento

## Query para verificar status no banco agrupado por departamento

Esta query mostra exatamente quais status existem no banco de dados, agrupados por departamento:

```sql
SELECT 
    d.id AS department_id,
    d.name AS department_name,
    tu.status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = 'Ativo'
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
INNER JOIN adms_departments d 
    ON u.user_department_id = d.id
WHERE tu.status IN (
    'em_dia',
    'dentro_do_prazo',
    'proximo_vencimento',
    'vencido',
    'agendado',
    'concluido'
)
GROUP BY 
    d.id,
    d.name,
    tu.status
ORDER BY 
    d.name,
    tu.status;
```

## Query para verificar todos os status (sem filtro)

Para ver TODOS os status, incluindo valores nulos ou inesperados:

```sql
SELECT 
    d.id AS department_id,
    d.name AS department_name,
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = 'Ativo'
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
INNER JOIN adms_departments d 
    ON u.user_department_id = d.id
GROUP BY 
    d.id,
    d.name,
    tu.status
ORDER BY 
    d.name,
    tu.status;
```

## Query resumida por status (todos os departamentos)

Para ver um resumo geral de quantos registros existem para cada status:

```sql
SELECT 
    COALESCE(tu.status, 'NULL') AS status,
    COUNT(*) AS total
FROM adms_training_users tu
INNER JOIN adms_users u 
    ON u.id = tu.adms_user_id 
   AND u.status = 'Ativo'
INNER JOIN adms_trainings t 
    ON t.id = tu.adms_training_id 
   AND t.ativo = 1
GROUP BY tu.status
ORDER BY total DESC;
```

## Análise dos Resultados

Após executar essas queries, você pode identificar:

1. **Se os status estão atualizados**: Verifique se existem registros com status `'em_dia'`, `'dentro_do_prazo'`, `'proximo_vencimento'`, etc.

2. **Se há status inesperados**: Pode haver valores como `'pendente'`, `NULL`, ou outros que não estão sendo contados.

3. **Se há registros sem status**: Registros com `status IS NULL` não serão contados pela query atual.

## Possíveis Problemas

1. **Status não atualizados**: Se a maioria dos registros ainda tem status antigo (ex: `'pendente'`), é necessário executar `updateDynamicStatuses()`.

2. **Status NULL**: Se muitos registros têm `status IS NULL`, eles não serão contados.

3. **Status diferentes**: Se os status no banco são diferentes dos esperados (ex: `'em_dia'` vs `'dentro_do_prazo'`), a query precisa ser ajustada.

