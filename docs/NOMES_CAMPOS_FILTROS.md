# ✅ Nomes de Campos Corretos para Filtros

## 📋 Configuração Atualizada

Os filtros do **Dashboard de Vendas - RMVendas** agora usam os **nomes de campos corretos** da query:

| Filtro | Nome do Campo | Tipo |
|--------|---------------|------|
| **Vendedor** | `nomeVendedor` | text |
| **Grupo de Parceiro** | `nomeGrupoPN` | text |
| **Ano** | `DataCriação` | year |
| **Mês** | `DataCriação` | month |

---

## 🔍 Por que Isso É Importante?

### Os nomes dos campos devem corresponder **exatamente** aos nomes na query SQL:

```sql
-- Da query RMVendas:
SELECT 
    T2."SlpName" AS "nomeVendedor",        -- ✅ Usar: nomeVendedor
    T7."GroupName" AS "nomeGrupoPN",       -- ✅ Usar: nomeGrupoPN
    T0."DocDate" AS "DataCriação",         -- ✅ Usar: DataCriação
    ...
FROM OINV T0
INNER JOIN OSLP T2 ON T0."SlpCode" = T2."SlpCode"
INNER JOIN OCRG T7 ON T6."GroupCode" = T7."GroupCode"
...
```

### ❌ Nomes Errados (Não Funcionam):
- `slpName` (falta alias correto)
- `Vendedor` (não existe na query)
- `groupName` (falta alias correto)

### ✅ Nomes Corretos (Funcionam):
- `nomeVendedor` (alias definido na query)
- `nomeGrupoPN` (alias definido na query)
- `DataCriação` (alias definido na query)

---

## 🎯 Como os Filtros Funcionam

### 1. **Filtro de Texto** (Vendedor, Grupo)

**Campo:** `nomeVendedor`

**Como funciona:**
- Sistema busca valores únicos de `nomeVendedor` na query
- Popula dropdown com a lista
- Ao filtrar, adiciona: `AND "nomeVendedor" = 'João Silva'`

### 2. **Filtro de Ano**

**Campo:** `DataCriação`  
**Tipo:** `year`

**Como funciona:**
- Sistema não busca valores (usa anos fixos: 2025-2021)
- Ao filtrar, adiciona: `AND YEAR(T0."DataCriação") = 2025`

### 3. **Filtro de Mês**

**Campo:** `DataCriação`  
**Tipo:** `month`

**Como funciona:**
- Sistema não busca valores (usa meses fixos: 1-12)
- Ao filtrar, adiciona: `AND MONTH(T0."DataCriação") = 11`

---

## 🔧 Como Verificar Nomes de Campos

### Método 1: Executar o Relatório

1. Acessar: **Menu → Relatórios → Relatórios Dinâmicos → RMVendas**
2. Clicar em **"Executar"**
3. Ver os **nomes das colunas** na tabela de resultados

### Método 2: Ver a SQL

1. Acessar: **Menu → Relatórios → Relatórios Dinâmicos → RMVendas**
2. Clicar em **"Editar"**
3. Ver a aba **"SQL Personalizado"**
4. Procurar por `AS "nomedocampo"`

**Exemplo:**
```sql
T2."SlpName" AS "nomeVendedor"
              ↑
              Este é o nome a usar no filtro
```

---

## 📝 Configuração do Dashboard (JSON)

A configuração atual dos filtros no banco de dados:

```json
{
  "filters_config": [
    {
      "field": "nomeVendedor",
      "label": "Vendedor",
      "type": "text",
      "variable": "{VENDEDOR}"
    },
    {
      "field": "nomeGrupoPN",
      "label": "Grupo de Parceiro",
      "type": "text",
      "variable": "{GRUPO_PARCEIRO}"
    },
    {
      "field": "DataCriação",
      "label": "Ano",
      "type": "year",
      "variable": "{ANO}"
    },
    {
      "field": "DataCriação",
      "label": "Mês",
      "type": "month",
      "variable": "{MES}"
    }
  ]
}
```

---

## ✅ Atualização Aplicada

O dashboard foi **atualizado automaticamente** com os nomes corretos:

```
✅ Dashboard atualizado com nomes de campos corretos!
   - nomeVendedor (para Vendedor)
   - nomeGrupoPN (para Grupo de Parceiro)
   - DataCriação (para Ano e Mês)
```

---

## 🧪 Testando Agora

### 1. **Recarregar a Página** (F5)

### 2. **Verificar Dropdowns:**

Os filtros devem carregar corretamente agora:

- ✅ **Vendedor:** Lista de vendedores
- ✅ **Grupo de Parceiro:** Lista de grupos
- ✅ **Ano:** 2025, 2024, 2023, 2022, 2021
- ✅ **Mês:** Janeiro...Dezembro

### 3. **Testar Filtrar:**

```
1. Selecione: Vendedor = (um da lista)
2. Selecione: Ano = 2025
3. Clique: "Consultar"
4. Veja: KPIs e gráficos atualizados!
```

---

## 🎓 Dica para Criar Novos Dashboards

### Sempre use os **nomes de campos exatos** da query:

1. **Executar o relatório** para ver os nomes das colunas
2. Ou **ver a SQL** e procurar por `AS "nomedocampo"`
3. Usar **exatamente esse nome** (case-sensitive)

**Exemplo Correto:**
```
Campo: nomeVendedor
      ↑
      Exatamente como aparece na query
```

**Exemplo Errado:**
```
Campo: vendedor
      ↑
      Não é o mesmo que "nomeVendedor"
```

---

## 📚 Referências

- Query RMVendas: Ver relatório ID 14
- Documentação de Filtros: `FILTROS_DINAMICOS_DASHBOARD.md`
- Guia do Dashboard Builder: `GUIA_DASHBOARD_BUILDER.md`

---

**✅ Nomes de campos corretos aplicados! Recarregue a página e teste!** 🚀

