# 🔧 Padrão de Correção para Foreign Keys em Migrations

## ❌ Problema

Migrations que tentam adicionar foreign keys diretamente no `create()` ou `update()` falham quando:
- A tabela referenciada não existe
- A tabela referenciada tem estrutura incompatível
- Há dados que violam a constraint

## ✅ Solução: Padrão Recomendado

### 1. Criar Tabela SEM Foreign Keys

```php
public function up(): void
{
    if (!$this->hasTable('minha_tabela')) {
        $table = $this->table('minha_tabela');
        $table
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('nome', 'string', ['limit' => 255])
            // NÃO adicionar foreign key aqui
            ->create();
    }
}
```

### 2. Adicionar Foreign Keys em Migration Separada (Opcional)

Se realmente precisar de foreign keys, criar uma migration separada que:
- Verifica se as tabelas existem
- Verifica se as colunas existem
- Verifica se os tipos são compatíveis
- Não falha se não conseguir adicionar

## 📋 Migrations Corrigidas

### ✅ Já Corrigidas:
1. `20250710160010` - CreateAdmsStrategicPlanObservations (sem FK)
2. `20250710160020` - AddForeignKeysToStrategicPlanObservations (com verificação)
3. `20251204000000` - AddEmploymentDatesToAdmsUsers (verifica índices)
4. `20251204010000` - CreateEmploymentHistoryTable (sem FK)
5. `20251206000000` - AddTwoStageApprovalToEmployeeRequests (sem FK)

## 🔍 Migrations que Precisam de Atenção

Migrations que ainda podem ter problemas:
- Todas que usam `addForeignKey` diretamente no `create()`
- Todas que não verificam se tabelas referenciadas existem

## 🎯 Regra de Ouro

**"Criar primeiro, relacionar depois"**

1. ✅ Criar tabelas sem foreign keys
2. ✅ Adicionar foreign keys depois (se necessário) em migration separada
3. ✅ Verificar sempre se tabelas/colunas existem antes de adicionar FK

