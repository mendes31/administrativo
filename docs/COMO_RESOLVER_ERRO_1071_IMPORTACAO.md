# Como Resolver o Erro #1071 ao Importar o Banco de Dados

## 🔴 Problema

Ao tentar importar o banco de dados MySQL, você recebe o erro:

```
#1071 - Specified key was too long; max key length is 767 bytes
```

## 📋 Causa

Este erro ocorre quando você tenta criar um índice único em uma coluna `VARCHAR(255)` com charset `utf8mb4`:

- `VARCHAR(255)` com `utf8mb4` = 255 × 4 bytes = **1020 bytes**
- Limite do MySQL para índices = **767 bytes** (em versões antigas)
- **1020 bytes > 767 bytes** = ❌ Erro!

## ✅ Solução

**Mantendo VARCHAR(255) e validando no PHP** (recomendado)

Como a validação de unicidade já é feita na aplicação PHP, podemos simplesmente **remover os índices únicos problemáticos** do banco de dados.

### Passo 1: Identificar as Tabelas Afetadas

As seguintes tabelas têm índices únicos em colunas VARCHAR(255):

- `adms_access_levels` (índice: `idx_unique_name`)
- `adms_departments` (índice: `idx_unique_name`)
- `adms_positions` (índice: `idx_unique_name`)
- `adms_payment_method` (índice: `idx_unique_name`)
- `adms_frequency` (índice: `idx_unique_name`)
- `adms_cost_center` (índice: `idx_unique_name`)
- `adms_accounts_plan` (índices: `idx_unique_name`, `idx_unique_account`)

### Passo 2: Executar o Script de Correção

#### Opção A: MySQL 5.7+ (recomendado)

Execute o script `scripts/fix_indices_mysql.sql`:

```sql
-- Este script usa DROP INDEX IF EXISTS (MySQL 5.7+)
```

**Como executar:**

1. Abra o phpMyAdmin
2. Selecione o banco de dados `administrativo`
3. Vá na aba "SQL"
4. Cole o conteúdo do arquivo `scripts/fix_indices_mysql.sql`
5. Clique em "Executar"

#### Opção B: MySQL 5.5 ou 5.6

Execute o script `scripts/fix_indices_mysql_old.sql`:

```sql
-- Este script usa DROP INDEX sem IF EXISTS (MySQL 5.5+)
```

**Nota:** Se algum índice não existir, o comando falhará - isso é normal, apenas continue.

### Passo 3: Importar o Dump SQL

Agora você pode importar o dump SQL normalmente:

1. Abra o phpMyAdmin
2. Selecione o banco de dados `administrativo`
3. Vá na aba "Importar"
4. Selecione o arquivo `.sql`
5. Clique em "Executar"

**Importante:** Se o dump SQL ainda tentar criar os índices problemáticos, você pode:

- **Opção 1:** Executar o script de correção **DEPOIS** da importação (mesmo que tenha erros)
- **Opção 2:** Editar o dump SQL antes de importar, removendo as linhas que criam os índices:
  ```sql
  -- Remover estas linhas do dump:
  ADD UNIQUE KEY `idx_unique_name` (`name`)
  ADD UNIQUE KEY `idx_unique_account` (`account`)
  ```

## 🔍 Verificar se Funcionou

Execute esta query para verificar se os índices foram removidos:

```sql
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM 
    information_schema.STATISTICS
WHERE 
    TABLE_SCHEMA = 'administrativo'
    AND INDEX_NAME IN ('idx_unique_name', 'idx_unique_account')
    AND TABLE_NAME IN (
        'adms_access_levels',
        'adms_departments',
        'adms_positions',
        'adms_payment_method',
        'adms_frequency',
        'adms_cost_center',
        'adms_accounts_plan'
    );
```

**Resultado esperado:** Nenhuma linha retornada (índices removidos)

## ✅ Validação no PHP

A validação de unicidade continua funcionando normalmente através do PHP:

- **Arquivo:** `app/adms/Controllers/Services/Validation/ValidationAccountPlanService.php`
- **Método:** `validateAccountPlan()`
- **Regra:** `uniqueInColumns:adms_accounts_plan,name`

A aplicação PHP valida a unicidade antes de inserir/atualizar, então não há problema em não ter o índice único no banco.

## 📝 Notas Importantes

1. **As colunas permanecem VARCHAR(255)** - não foram alteradas
2. **A validação de unicidade é feita no PHP** - não há perda de funcionalidade
3. **O desempenho pode ser ligeiramente afetado** - mas para tabelas pequenas/médias, o impacto é mínimo
4. **Se precisar dos índices únicos no futuro**, você pode usar prefixos:
   ```sql
   ADD UNIQUE KEY `idx_unique_name` (`name`(191))
   ```

## 🚀 Próximos Passos

Após resolver o erro de importação:

1. Execute as migrations do Phinx (se necessário):
   ```bash
   php vendor/bin/phinx migrate
   ```

2. Execute os seeds (se necessário):
   ```bash
   php vendor/bin/phinx seed:run
   ```

3. Verifique se a aplicação está funcionando corretamente

---

**Última atualização:** 2025-02-05

