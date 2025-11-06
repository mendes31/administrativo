# 🎯 Exemplo Prático: Dashboard de Vendas - RMVendas

## 📋 Resumo

Este é um exemplo completo de dashboard criado usando o relatório **RMVendas** do SAP B1.

**Dashboard:** Dashboard de Vendas - RMVendas (ID: 5)
**Relatório Base:** RMVendas (ID: 14)
**Criado automaticamente pela seed:** `InsertDashboardRMVendas.php`

---

## 🎨 Estrutura do Dashboard

### 📐 4 Medidas Calculadas

| Medida | Fórmula | Formato | Descrição |
|--------|---------|---------|-----------|
| **Ticket Médio** | `[Total c/ Desc] / COUNT([NumDoc])` | currency | Valor médio por documento |
| **Margem %** | `([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100` | percent | Margem de lucro percentual |
| **Qtd Total Vendida** | `SUM([Qtde])` | number | Soma de todas as quantidades |
| **Custo Total** | `SUM([Custo_Total_Item])` | currency | Soma de todos os custos |

---

### 📊 6 KPIs

| # | Campo | Rótulo | Agregação | Formato | Ícone | Cor |
|---|-------|--------|-----------|---------|-------|-----|
| 1 | Total c/ Desc | **Faturamento Total** | sum | currency | fa-dollar-sign | success |
| 2 | Ticket Médio | **Ticket Médio** | - | currency | fa-receipt | info |
| 3 | NumDoc | **Total de Documentos** | count_distinct | number | fa-file-invoice | primary |
| 4 | Margem % | **Margem Média** | - | percent | fa-percentage | warning |
| 5 | Qtd Total Vendida | **Quantidade Vendida** | - | number | fa-boxes | secondary |
| 6 | Custo Total | **Custo Total** | - | currency | fa-coins | danger |

---

### 🔍 4 Filtros

| Filtro | Campo | Tipo | Variável | Descrição |
|--------|-------|------|----------|-----------|
| **Vendedor** | nomeVendedor | text | {VENDEDOR} | Filtrar por nome do vendedor |
| **Grupo de Parceiro** | nomeGrupoPN | text | {GRUPO_PARCEIRO} | Filtrar por grupo de parceiro de negócios |
| **Ano** | YEAR(DataCriação) | year | {ANO} | Filtrar por ano |
| **Mês** | MONTH(DataCriação) | month | {MES} | Filtrar por mês (1-12) |

---

### 📈 4 Gráficos

| # | Tipo | Agrupado por | Valor | Agregação | Título |
|---|------|--------------|-------|-----------|--------|
| 1 | **Barras** | nomeVendedor | Total c/ Desc | sum | Faturamento por Vendedor |
| 2 | **Pizza** | nomeGrupoPN | Total c/ Desc | sum | Distribuição por Grupo de Parceiro |
| 3 | **Barras** | nomeGrupoItem | Qtde | sum | Quantidade Vendida por Grupo de Item |
| 4 | **Rosca** | nomeVendedor | NumDoc | count | Documentos por Vendedor |

---

## 🔄 Como Usar o Dashboard

### 1. Acessar o Dashboard

```
Menu → Relatórios → Meus Dashboards → "Dashboard de Vendas - RMVendas"
```

### 2. Visualizar KPIs

No topo da página, você verá **6 cards coloridos** com os indicadores principais:
- 🟢 Verde: Faturamento Total
- 🔵 Azul: Ticket Médio
- 🔵 Azul: Total de Documentos
- 🟡 Amarelo: Margem Média
- ⚫ Cinza: Quantidade Vendida
- 🔴 Vermelho: Custo Total

### 3. Aplicar Filtros

Na lateral ou topo do dashboard, você encontrará **4 filtros**:

**Exemplo de uso:**
```
Vendedor: João Silva
Grupo de Parceiro: Clientes Premium
Ano: 2025
Mês: 11 (Novembro)
```

Após preencher os filtros desejados, clique em **"Consultar"** ou **"Aplicar Filtros"**.

### 4. Analisar Gráficos

O dashboard exibirá **4 gráficos** com visualizações diferentes:

1. **Gráfico de Barras** - Mostra quanto cada vendedor faturou
2. **Gráfico de Pizza** - Mostra a distribuição percentual por grupo de parceiro
3. **Gráfico de Barras** - Mostra quantidade vendida por grupo de item
4. **Gráfico de Rosca** - Mostra quantos documentos cada vendedor gerou

---

## 💡 Casos de Uso

### Caso 1: Análise de Performance de Vendedores

**Objetivo:** Ver qual vendedor está faturando mais em 2025

**Passos:**
1. Filtrar por **Ano: 2025**
2. Deixar os outros filtros vazios
3. Consultar
4. Analisar o gráfico **"Faturamento por Vendedor"**
5. Ver KPI **"Faturamento Total"** para o total geral

**Resultado:** Identificar os vendedores top performers

---

### Caso 2: Análise de Grupo de Clientes

**Objetivo:** Descobrir qual grupo de clientes gera mais receita

**Passos:**
1. Filtrar por **Ano: 2025**
2. Deixar **Vendedor** e **Grupo de Parceiro** vazios
3. Consultar
4. Analisar o gráfico **"Distribuição por Grupo de Parceiro"**

**Resultado:** Ver visualmente a distribuição de faturamento por grupo

---

### Caso 3: Performance Mensal de um Vendedor

**Objetivo:** Ver como está o desempenho de "João Silva" em Novembro

**Passos:**
1. Filtrar por **Vendedor: João Silva**
2. Filtrar por **Ano: 2025**
3. Filtrar por **Mês: 11**
4. Consultar
5. Ver todos os KPIs (Faturamento, Ticket Médio, Margem, etc.)

**Resultado:** Dashboard completo focado em um vendedor específico em um período

---

### Caso 4: Análise de Margem

**Objetivo:** Verificar se a margem de lucro está saudável

**Passos:**
1. Aplicar os filtros desejados (ou deixar todos vazios para visão geral)
2. Consultar
3. Observar o KPI **"Margem Média"** (deve ser > 20% para ser saudável)
4. Comparar **"Faturamento Total"** com **"Custo Total"**

**Resultado:** Entender a rentabilidade das vendas

---

## 🎓 Como Este Dashboard Foi Criado

### Passo a Passo Manual

Se você quiser criar um dashboard similar manualmente:

1. **Acessar:** Menu → Relatórios → Meus Dashboards → Criar Novo Dashboard

2. **Etapa 1 - Selecionar Relatórios:**
   - Marcar: ✅ RMVendas
   - Clicar em: **Próximo**

3. **Etapa 2 - Configurar Dashboard:**

   **Informações Básicas:**
   ```
   Nome: Dashboard de Vendas - RMVendas
   Categoria: Vendas
   Descrição: Dashboard completo de vendas
   Público: Sim
   ```

   **Medidas Calculadas:** (clicar em "Nova Medida" 4 vezes)
   ```
   Medida 1:
   - Nome: Ticket Médio
   - Fórmula: [Total c/ Desc] / COUNT([NumDoc])
   - Formato: currency

   Medida 2:
   - Nome: Margem %
   - Fórmula: ([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100
   - Formato: percent

   Medida 3:
   - Nome: Qtd Total Vendida
   - Fórmula: SUM([Qtde])
   - Formato: number

   Medida 4:
   - Nome: Custo Total
   - Fórmula: SUM([Custo_Total_Item])
   - Formato: currency
   ```

   **KPIs:** (clicar em "Adicionar KPI" 6 vezes ou arrastar campos)
   ```
   Configurar cada um conforme a tabela de KPIs acima
   ```

   **Filtros:** (clicar em "Adicionar Filtro" 4 vezes)
   ```
   Configurar cada um conforme a tabela de Filtros acima
   ```

   **Gráficos:** (clicar em "Adicionar Gráfico" 4 vezes ou arrastar campos)
   ```
   Configurar cada um conforme a tabela de Gráficos acima
   ```

4. **Salvar:** Clicar em **"Criar Dashboard"**

---

## 🔧 Customizando o Dashboard

Você pode **editar** este dashboard para:

- ✏️ Adicionar mais medidas calculadas
- ➕ Adicionar mais KPIs
- 🔍 Adicionar mais filtros (ex: Estado, Filial)
- 📊 Adicionar mais gráficos (ex: Linha de tendência)
- 🎨 Mudar cores e ícones dos KPIs

**Como editar:**
```
Menu → Relatórios → Meus Dashboards → Dashboard de Vendas - RMVendas → [Botão Editar]
```

---

## 📚 Campos Disponíveis no Relatório RMVendas

Para criar novas medidas ou configurações, você pode usar qualquer um destes campos:

### Documentos
- NumDoc, DocEntry, NFe, TipoDocumento, DocStatus

### Datas
- DataCriação, YEAR(DataCriação), MONTH(DataCriação)

### Parceiro de Negócios
- cdPN, nomePN, EndereçoCliente, nomeGrupoPN, Estado, País

### Vendedor
- nomeVendedor

### Itens
- cdItem, nomeItem, nomeGrupoItem, Qtde

### Valores
- Custo_Unitário, Custo_Total_Item, Total Impostos, Custo_+_Impostos
- Preco Unitario, Desconto%, Preco Final
- Total s/ Desc, Total c/ Desc, Valor final
- Desc Rodapé, Distribuicao Desc Rodapé

### Outros
- Filial, Moeda, Utilizacao

---

## 🚀 Próximos Passos

1. ✅ **Testar o dashboard** com dados reais
2. ✅ **Aplicar filtros** para diferentes cenários
3. ✅ **Compartilhar** com sua equipe (já está público)
4. 📝 **Personalizar** conforme suas necessidades
5. 📊 **Criar novos dashboards** baseados neste exemplo

---

**💡 Dica Final:** Use este dashboard como **template** para criar outros dashboards de vendas, apenas ajustando os filtros e gráficos conforme a necessidade!

**🎉 Bom proveito!** 🚀

