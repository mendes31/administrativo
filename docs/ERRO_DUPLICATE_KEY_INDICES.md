# Solução para Erro "Duplicate key name" ao Criar Índices

## ❌ Erro Encontrado

```
#1061 - Duplicate key name 'idx_crm_partners_responsible_user'
```

Este erro significa que o índice **já existe** na tabela. Isso é **normal** e **esperado**!

---

## ✅ O Que Fazer

### Opção 1: Ignorar o Erro (RECOMENDADO)

O erro `#1061` é informativo - significa que o índice já foi criado anteriormente. Você pode:

1. **Ignorar o erro** e continuar executando o script
2. Os outros índices que ainda não existem **serão criados normalmente**
3. No final, execute o script de verificação para ver quais índices foram criados

### Opção 2: Usar Script Seguro

Criei um script que **verifica se o índice existe antes de criar**:

**Arquivo:** `scripts/add_performance_indexes_crm_hierarchy_SEGURO.sql`

Este script:
- ✅ Verifica se o índice já existe
- ✅ Só cria se não existir
- ✅ Não gera erros de "Duplicate key name"

**Como usar:**
1. No phpMyAdmin, aba **SQL**
2. Abra: `scripts/add_performance_indexes_crm_hierarchy_SEGURO.sql`
3. Copie e cole no phpMyAdmin
4. Execute

---

## 🔍 Verificar Quais Índices Já Existem

Execute o script de verificação:

**Arquivo:** `scripts/verificar_indices.sql`

Este script mostra:
- ✅ Quais índices já foram criados
- ❌ Quais índices ainda estão faltando

---

## 📝 Resumo

1. **Erro `#1061` = Índice já existe** ✅ (Isso é bom!)
2. **Continue executando** - outros índices serão criados
3. **Ou use o script SEGURO** que verifica antes de criar
4. **Verifique no final** com o script de verificação

---

**Última atualização:** 2025-02-05

