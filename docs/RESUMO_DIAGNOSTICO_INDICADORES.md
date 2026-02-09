# Resumo: Diagnóstico dos Indicadores Zerados

## Problema

Os indicadores "A Fazer (Dentro do Prazo)" e "Pendentes (Próx. Vencimento)" estão zerados na tabela "Estatísticas por Departamento".

## Análise da Query Atual

A query em `getDepartmentStatistics()` está **correta** e busca diretamente de `tu.status` no banco:

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

## Possíveis Causas

1. **Status não atualizados no banco**: Mesmo após executar `update_training_statuses.php`, os status podem não ter sido atualizados corretamente
2. **Status diferentes do esperado**: Pode haver status como `'pendente'` ou `NULL` que não estão sendo contados
3. **Problema no método `updateDynamicStatuses()`**: O método pode não estar atualizando corretamente todos os registros

## Como Diagnosticar

### 1. Execute o script de teste no servidor:

```bash
cd ~/www/administrativo
php scripts/test_department_stats_query.php
```

Este script vai mostrar:
- Quais status existem no banco
- Status por departamento
- Comparação entre total geral vs. soma por departamento
- Resultado da query exata do dashboard

### 2. Execute esta query no phpMyAdmin:

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

Isso mostra **exatamente** quais status existem por departamento.

### 3. Verifique se há registros com status NULL ou inesperados:

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

## Solução

Após executar o diagnóstico, você saberá:

1. **Se os status existem no banco**: Se a query mostrar registros com `'em_dia'`, `'dentro_do_prazo'` ou `'proximo_vencimento'`, mas eles não aparecem no dashboard, o problema está na query ou no processamento PHP.

2. **Se os status não existem**: Se a query não mostrar esses status, o problema está no método `updateDynamicStatuses()` que não está atualizando corretamente.

3. **Se há status inesperados**: Se houver muitos registros com `'pendente'` ou `NULL`, esses precisam ser atualizados.

## Próximos Passos

1. Execute o script de teste: `php scripts/test_department_stats_query.php`
2. Compartilhe o resultado para ajustarmos a query se necessário
3. Se os status não existirem no banco, precisamos corrigir o método `updateDynamicStatuses()`

