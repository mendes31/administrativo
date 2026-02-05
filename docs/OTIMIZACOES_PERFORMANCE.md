# Otimizações de Performance Implementadas

## 📊 Problemas Identificados

### 1. **Página "Status de Treinamentos por Colaborador"**

**Problemas encontrados:**
- ❌ Carregava TODOS os registros sem paginação (1.364+ registros)
- ❌ Problema N+1: Para cada registro, fazia uma query separada para buscar histórico
- ❌ Query executada DUAS VEZES no controller
- ❌ Filtragem de status feita em PHP após buscar todos os dados
- ❌ Sem índices otimizados para JOINs

**Impacto:** Página levava vários segundos para carregar, especialmente com muitos registros.

---

## ✅ Otimizações Implementadas

### 1. **Paginação Adicionada**

**Arquivo:** `app/adms/Models/Repository/TrainingUsersRepository.php`

- ✅ Método `getTrainingStatusByUser()` agora aceita `$page` e `$perPage`
- ✅ Retorna apenas os registros da página solicitada
- ✅ Retorna informações de paginação (total, total_pages, current_page)
- ✅ Padrão: 50 registros por página (configurável)

**Benefício:** Reduz drasticamente a quantidade de dados carregados.

### 2. **Problema N+1 Resolvido**

**Antes:**
```php
// Para cada registro, uma query separada
foreach ($results as &$result) {
    $history = $appRepo->getHistoryAfter(...); // Query individual
}
```

**Depois:**
```sql
-- Uma única query com LEFT JOIN
LEFT JOIN (
    SELECT ... FROM adms_training_applications
    -- Subquery otimizada para última aplicação
) ta_last ON ...
```

**Benefício:** 
- **Antes:** 1 query principal + N queries (1.364 queries adicionais)
- **Depois:** 1 query única
- **Redução:** ~99.9% de queries

### 3. **Query Duplicada Removida**

**Antes:**
```php
$this->data['matrix'] = $trainingUsersRepo->getTrainingStatusByUser($filters); // Query 1
$matrix = $trainingUsersRepo->getTrainingStatusByUser($filters); // Query 2 (duplicada)
```

**Depois:**
```php
$matrixResult = $trainingUsersRepo->getTrainingStatusByUser($filters, $page, $perPage);
$this->data['matrix'] = $matrixResult['data'];
```

**Benefício:** Elimina 50% das queries desnecessárias.

### 4. **Filtragem Otimizada**

- ✅ Filtros aplicados diretamente no SQL quando possível
- ✅ Filtro de status dinâmico aplicado após calcular (necessário para lógica de negócio)
- ✅ Filtros de colaborador, departamento, cargo, treinamento aplicados no SQL

### 5. **Índices de Performance**

**Arquivo:** `scripts/add_performance_indexes_training.sql`

Índices adicionados para melhorar JOINs e filtros:

- `idx_training_users_user_status` - Melhora filtros por usuário e status
- `idx_training_users_training_status` - Melhora filtros por treinamento e status
- `idx_training_applications_user_training_created` - **CRÍTICO** - Resolve N+1
- `idx_users_status_department` - Melhora filtros por departamento
- `idx_trainings_ativo_codigo` - Melhora busca por código

**Como aplicar:**
```bash
# Via phpMyAdmin ou linha de comando
mysql -u usuario -p nome_banco < scripts/add_performance_indexes_training.sql
```

---

## 📈 Resultados Esperados

### Antes das Otimizações:
- ⏱️ **Tempo de carregamento:** 5-15 segundos
- 🔢 **Queries executadas:** ~1.365 queries
- 💾 **Memória utilizada:** Alta (todos os registros em memória)
- 📊 **Registros carregados:** 1.364+ (todos)

### Depois das Otimizações:
- ⏱️ **Tempo de carregamento:** 0.5-2 segundos
- 🔢 **Queries executadas:** 1-2 queries
- 💾 **Memória utilizada:** Baixa (apenas 50 registros por página)
- 📊 **Registros carregados:** 50 por página

**Melhoria estimada:** **90-95% mais rápido** 🚀

---

## 🔧 Como Usar

### 1. **Aplicar Índices (OBRIGATÓRIO)**

Execute o script SQL para adicionar os índices:

```sql
-- Via phpMyAdmin ou linha de comando
source scripts/add_performance_indexes_training.sql;
```

### 2. **Verificar Paginação**

A paginação já está implementada e funcionando automaticamente. Os usuários verão:
- Controles de paginação na parte inferior da tabela
- Informação de "Mostrando X a Y de Z registros"
- Botões "Anterior" e "Próximo"

### 3. **Configurar Registros por Página**

O número de registros por página é configurado automaticamente baseado na resolução da tela (responsivo). Para alterar manualmente:

**Arquivo:** `app/adms/Controllers/trainings/ListTrainingStatus.php`

```php
$perPage = 50; // Alterar aqui (padrão: 50)
```

---

## 🔍 Verificação de Performance

### Verificar se os índices foram criados:

```sql
SHOW INDEX FROM adms_training_users;
SHOW INDEX FROM adms_training_applications;
SHOW INDEX FROM adms_users;
```

### Monitorar queries lentas:

```sql
-- Habilitar log de queries lentas (MySQL)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Queries > 1 segundo
```

### Verificar uso de índices:

```sql
EXPLAIN SELECT ... -- Adicionar sua query aqui
```

---

## 📝 Próximas Otimizações Sugeridas

### 1. **Cache de Queries Frequentes**

Implementar cache para:
- Lista de departamentos (`getAllDepartmentsSelect`)
- Lista de cargos (`getAllPositionsSelect`)
- Lista de treinamentos (`getAllTrainingsSelect`)
- Resumo de status (`getSummaryAll`)

**Benefício:** Reduz queries repetitivas em cada carregamento de página.

### 2. **Lazy Loading de Dados Secundários**

Carregar dados de histórico apenas quando necessário (on-demand).

### 3. **Otimização de Queries de Resumo**

O método `getSummaryAll()` pode ser otimizado para usar uma única query agregada.

### 4. **Implementar Paginação em Outras Páginas**

Aplicar a mesma estratégia de paginação em:
- Lista de usuários
- Lista de treinamentos
- Outras listagens grandes

---

## ⚠️ Notas Importantes

1. **Compatibilidade:** O método legado `getTrainingStatusByUserLegacy()` foi mantido para compatibilidade, mas está marcado como `@deprecated`.

2. **Filtros:** Os filtros continuam funcionando normalmente, agora com melhor performance.

3. **Status Dinâmico:** O cálculo de status dinâmico ainda é feito em PHP (necessário para lógica de negócio), mas apenas para os registros da página atual.

4. **Índices:** Os índices são essenciais para o bom desempenho. Sem eles, a melhoria será menor.

---

## 🐛 Troubleshooting

### Página ainda lenta após otimizações:

1. **Verificar se os índices foram criados:**
   ```sql
   SHOW INDEX FROM adms_training_users;
   ```

2. **Verificar se há muitos registros sem filtros:**
   - Use filtros para reduzir o conjunto de dados
   - A paginação ajuda, mas filtros são ainda melhores

3. **Verificar configuração do MySQL:**
   - `innodb_buffer_pool_size` deve ser adequado
   - Verificar se há outras queries lentas bloqueando

### Erro "Column 'X' cannot be null":

- Verificar se todos os campos necessários estão sendo retornados na query
- Verificar se os JOINs estão corretos

---

**Última atualização:** 2025-02-05  
**Versão:** 1.0

