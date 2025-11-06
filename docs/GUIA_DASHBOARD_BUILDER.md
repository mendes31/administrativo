# 📊 Guia Completo - Dashboard Builder (Estilo Power BI)

## 🎯 Onde Estamos no Projeto

### ✅ Funcionalidades Implementadas

1. **📐 Medidas Calculadas** - Criar métricas personalizadas com fórmulas estilo DAX/Power BI
2. **📈 KPIs** - Indicadores chave de performance com agregações (SUM, AVG, COUNT, etc.)
3. **🎨 Gráficos** - Visualizações com agrupamento e agregação de dados
4. **🔍 Filtros** - Filtros dinâmicos para interagir com o dashboard
5. **🖱️ Drag & Drop** - Arrastar campos para configurar KPIs e gráficos
6. **📊 Multi-Relatórios** - Usar dados de vários relatórios em um único dashboard
7. **🔒 Permissões** - Dashboards públicos ou privados

---

## 📖 Como Usar o Dashboard Builder

### Passo 1: Acessar o Criador de Dashboards

1. No menu lateral, vá em **"Relatórios"** → **"Meus Dashboards"**
2. Clique em **"Criar Novo Dashboard"** ou
3. Em um relatório existente, clique no botão **"Criar Dashboard"**

---

### Passo 2: Selecionar Relatórios Base (Etapa 1)

<img src="https://via.placeholder.com/800x400?text=Etapa+1+-+Selecionar+Relat%C3%B3rios" alt="Etapa 1">

1. **Marque um ou mais relatórios** que deseja usar como fonte de dados
2. Os campos desses relatórios ficarão disponíveis na etapa 2
3. Clique em **"Próximo: Configurar Dashboard"**

> 💡 **Dica**: Selecione apenas os relatórios necessários para melhor performance

---

### Passo 3: Configurar Dashboard (Etapa 2)

#### 3.1 Informações Básicas

- **Nome**: Nome descritivo do dashboard (ex: "Dashboard de Vendas 2025")
- **Categoria**: Classificação (ex: Vendas, Financeiro, Estoque)
- **Descrição**: Breve descrição do objetivo do dashboard
- **Visibilidade**: Público (todos veem) ou Privado (só você)

---

#### 3.2 📐 Medidas Calculadas

As medidas calculadas permitem criar métricas personalizadas usando fórmulas.

**Como criar:**

1. Clique em **"Nova Medida"**
2. Digite o **nome da medida** (ex: "Ticket Médio", "% Desconto")
3. Digite a **fórmula** (veja exemplos abaixo)
4. Escolha o **formato** (number, currency, percent)

**Exemplos de Fórmulas:**

```dax
# Ticket Médio
[Total c/ Desc] / COUNT([NumDoc])

# Percentual de Desconto sobre o Total
[Desc Rodapé] / [Total c/ Desc] * 100

# Soma com Filtro (estilo Power BI)
CALCULATE(SUM([Total c/ Desc]);[nomeVendedor]="João Silva")

# Margem de Lucro
([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100

# Faturamento Apenas de Vendas (com filtro)
CALCULATE(SUM([Total c/ Desc]);[TipoDocumento]="Fatura")
```

**Funções Disponíveis:**
- `SUM([campo])` - Soma
- `AVG([campo])` - Média
- `COUNT([campo])` - Contagem
- `MIN([campo])` - Mínimo
- `MAX([campo])` - Máximo
- `COUNT_DISTINCT([campo])` - Contagem de valores únicos
- `CALCULATE(expressão;filtro1;filtro2)` - Aplica filtros ao contexto

**Referências a Campos:**
- `[Campo]` - Referência simples
- `TABELA[Campo]` - Referência com nome da tabela (estilo Power BI)

**Filtros:**
- `[Campo]="Valor"` - Filtro de texto
- `TABELA[Campo]="Valor"` - Filtro com tabela
- `[Campo]=123` - Filtro numérico
- Múltiplos filtros separados por `;` (ponto e vírgula)

---

#### 3.3 📈 Configurar KPIs

KPIs são indicadores numéricos destacados no topo do dashboard.

**Como adicionar:**

1. Clique em **"Adicionar KPI"** ou
2. **Arraste um campo** do painel direito para a área de KPIs
3. Configure:
   - **Campo**: Nome do campo ou medida calculada
   - **Rótulo**: Texto a exibir (ex: "Faturamento Total")
   - **Agregação**: sum, avg, count, count_distinct, min, max
   - **Formato**: number, currency, percent
   - **Ícone**: Ícone FontAwesome (ex: fa-dollar-sign, fa-chart-line)
   - **Cor**: primary, success, danger, warning, info

**Exemplo de KPI:**
```
Campo: Total c/ Desc
Rótulo: Faturamento Total
Agregação: sum
Formato: currency
Ícone: fa-dollar-sign
Cor: success
```

**Usando Medida Calculada em KPI:**
```
Campo: Ticket Médio (medida criada anteriormente)
Rótulo: Ticket Médio
Agregação: (não precisa, pois a medida já calcula)
Formato: currency
Ícone: fa-receipt
Cor: info
```

---

#### 3.4 🔍 Configurar Filtros

Filtros permitem que os usuários interajam com o dashboard.

**Como adicionar:**

1. Clique em **"Adicionar Filtro"**
2. Configure:
   - **Campo**: Nome do campo para filtrar (ex: "nomeVendedor", "DataCriação")
   - **Rótulo**: Nome do filtro na interface (ex: "Vendedor", "Ano")
   - **Tipo**: text, number, date, year, month
   - **Variável**: Marcador na SQL (ex: {VENDEDOR}, {ANO})

**Exemplo de Filtros:**
```
# Filtro de Vendedor
Campo: nomeVendedor
Rótulo: Vendedor
Tipo: text
Variável: {VENDEDOR}

# Filtro de Ano
Campo: YEAR(DataCriação)
Rótulo: Ano
Tipo: year
Variável: {ANO}

# Filtro de Mês
Campo: MONTH(DataCriação)
Rótulo: Mês
Tipo: month
Variável: {MES}
```

> ⚠️ **Importante**: Os filtros precisam estar configurados na SQL do relatório base com as variáveis correspondentes.

---

#### 3.5 🎨 Configurar Gráficos

Gráficos visualizam os dados agregados.

**Como adicionar:**

1. Clique em **"Adicionar Gráfico"** ou
2. **Arraste um campo** do painel direito para a área de gráficos
3. Configure:
   - **Tipo**: bar, line, pie, doughnut
   - **Agrupar por**: Campo para agrupar (ex: "nomeVendedor", "nomeGrupoPN")
   - **Campo de Valor**: Campo a agregar (ex: "Total c/ Desc", "Qtde")
   - **Agregação**: sum, avg, count
   - **Título**: Título do gráfico

**Exemplo de Gráfico:**
```
Tipo: bar
Agrupar por: nomeVendedor
Campo de Valor: Total c/ Desc
Agregação: sum
Título: Faturamento por Vendedor
```

---

### Passo 4: Painel de Campos Disponíveis

À direita da tela, você verá um painel com **todos os campos** dos relatórios selecionados, organizados por:

1. **📐 Medidas Calculadas** (roxo) - Medidas criadas por você
2. **📊 Nome do Relatório** (azul) - Campos do relatório

**Como usar:**
- **Clique** no campo para ver detalhes
- **Arraste** o campo para KPIs, Filtros ou Gráficos
- **Duplo clique** para adicionar rapidamente a um KPI

---

### Passo 5: Salvar o Dashboard

1. Revise todas as configurações
2. Clique em **"Criar Dashboard"** (botão verde)
3. Aguarde a confirmação
4. Seu dashboard estará disponível em **"Meus Dashboards"**

---

## 🎯 Exemplo Prático: Dashboard de Vendas

### Cenário:
Criar um dashboard de vendas com faturamento, filtros por vendedor, ano, mês e grupo de parceiro.

### Relatório Base:
**RMVendas** (relatório existente com dados de vendas do SAP B1)

### Configuração:

#### 1. Medidas Calculadas

```dax
# Medida 1: Ticket Médio
Nome: Ticket Médio
Fórmula: [Total c/ Desc] / COUNT([NumDoc])
Formato: currency

# Medida 2: Margem (%)
Nome: Margem %
Fórmula: ([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100
Formato: percent

# Medida 3: Quantidade Total
Nome: Qtd Total Vendida
Fórmula: SUM([Qtde])
Formato: number
```

#### 2. KPIs

```
KPI 1:
- Campo: Total c/ Desc
- Rótulo: Faturamento Total
- Agregação: sum
- Formato: currency
- Ícone: fa-dollar-sign
- Cor: success

KPI 2:
- Campo: Ticket Médio (medida)
- Rótulo: Ticket Médio
- Formato: currency
- Ícone: fa-receipt
- Cor: info

KPI 3:
- Campo: NumDoc
- Rótulo: Total de Documentos
- Agregação: count_distinct
- Formato: number
- Ícone: fa-file-invoice
- Cor: primary

KPI 4:
- Campo: Margem % (medida)
- Rótulo: Margem Média
- Formato: percent
- Ícone: fa-percentage
- Cor: warning
```

#### 3. Filtros

```
Filtro 1:
- Campo: nomeVendedor
- Rótulo: Vendedor
- Tipo: text

Filtro 2:
- Campo: nomeGrupoPN
- Rótulo: Grupo de Parceiro
- Tipo: text

Filtro 3:
- Campo: DataCriação
- Rótulo: Ano
- Tipo: year

Filtro 4:
- Campo: DataCriação
- Rótulo: Mês
- Tipo: month
```

#### 4. Gráficos

```
Gráfico 1:
- Tipo: bar
- Agrupar por: nomeVendedor
- Campo de Valor: Total c/ Desc
- Agregação: sum
- Título: Faturamento por Vendedor

Gráfico 2:
- Tipo: pie
- Agrupar por: nomeGrupoPN
- Campo de Valor: Total c/ Desc
- Agregação: sum
- Título: Distribuição por Grupo de Parceiro

Gráfico 3:
- Tipo: line
- Agrupar por: DataCriação
- Campo de Valor: Total c/ Desc
- Agregação: sum
- Título: Evolução de Vendas
```

---

## 💡 Dicas e Boas Práticas

### ✅ DO (Faça)

1. **Nomeie claramente** suas medidas e KPIs
2. **Use medidas calculadas** para métricas complexas
3. **Teste as fórmulas** antes de salvar
4. **Limite o número de KPIs** a 4-6 por dashboard
5. **Use cores** para destacar informações importantes
6. **Documente** suas fórmulas complexas

### ❌ DON'T (Não faça)

1. Não crie **muitos gráficos** em um dashboard (máx. 4-6)
2. Não use **nomes genéricos** (ex: "KPI 1", "Medida A")
3. Não **misture formatos** incorretamente (ex: usar percent para valores absolutos)
4. Não **duplique informações** (mesma métrica em vários KPIs)
5. Não **esqueça de testar** os filtros após criar

---

## 🐛 Resolução de Problemas

### Problema: "Fórmula inválida!"
- **Causa**: Caracteres especiais não permitidos ou sintaxe incorreta
- **Solução**: Use apenas funções permitidas (SUM, AVG, COUNT, CALCULATE, etc.) e operadores (+, -, *, /, ())

### Problema: "Campo não encontrado"
- **Causa**: O nome do campo não existe no relatório
- **Solução**: Verifique os nomes exatos dos campos no painel direito

### Problema: KPI mostra valor errado
- **Causa**: Agregação incorreta ou medida mal configurada
- **Solução**: Revise a fórmula da medida ou a agregação do KPI

### Problema: Filtros não funcionam
- **Causa**: Variáveis não configuradas na SQL do relatório
- **Solução**: Edite o relatório e adicione as variáveis na SQL

---

## 📚 Recursos Adicionais

- **Documentação de Fórmulas**: Ver `DASHBOARD_FORMULAS.md`
- **Exemplos de Dashboards**: Ver dashboard "Dashboard de Vendas SAP B1"
- **Suporte**: Contate o administrador do sistema

---

**🎉 Pronto! Agora você está apto a criar dashboards poderosos estilo Power BI!** 🚀

