# ⚡ Guia Rápido - Dashboard Builder

## 🚀 Início Rápido (5 Minutos)

### 1. Criar Novo Dashboard
```
Menu → Relatórios → Meus Dashboards → Criar Novo Dashboard
```

### 2. Selecionar Relatório
✅ Marcar o(s) relatório(s) que deseja usar → **Próximo**

### 3. Configurar

#### 📐 Medida Calculada
```
Clicar: "Nova Medida"
Nome: Ticket Médio
Fórmula: [TotalLinha] / COUNT([NumDoc])
Formato: currency
```

#### 📈 KPI
```
Clicar: "Adicionar KPI" OU Arrastar campo
Campo: Total c/ Desc
Rótulo: Faturamento Total
Agregação: sum
Formato: currency
```

#### 🔍 Filtro
```
Clicar: "Adicionar Filtro"
Campo: nomeVendedor
Rótulo: Vendedor
Tipo: text
```

#### 📊 Gráfico
```
Clicar: "Adicionar Gráfico" OU Arrastar campo
Tipo: bar
Agrupar por: nomeVendedor
Valor: Total c/ Desc
Agregação: sum
Título: Vendas por Vendedor
```

### 4. Salvar
**Criar Dashboard** → ✅ Pronto!

---

## 📐 Fórmulas Rápidas

### Básicas
```dax
# Soma
SUM([Campo])

# Média
AVG([Campo])

# Contagem
COUNT([Campo])

# Divisão
[CampoA] / [CampoB]

# Percentual
[Parte] / [Total] * 100
```

### Com CALCULATE
```dax
# Soma com filtro
CALCULATE(SUM([Valor]);[Tipo]="Venda")

# Múltiplos filtros
CALCULATE(SUM([Valor]);[Ano]=2025;[Mes]=11)

# CALCULATE aninhado
CALCULATE([A] / CALCULATE(SUM([B]);[C]="X"))
```

---

## 🎨 Configurações Rápidas de KPI

### Faturamento
```
Campo: Total c/ Desc
Rótulo: Faturamento Total
Agregação: sum
Formato: currency
Ícone: fa-dollar-sign
Cor: success
```

### Quantidade
```
Campo: Qtde
Rótulo: Quantidade Total
Agregação: sum
Formato: number
Ícone: fa-boxes
Cor: primary
```

### Documentos
```
Campo: NumDoc
Rótulo: Nº de Documentos
Agregação: count_distinct
Formato: number
Ícone: fa-file-invoice
Cor: info
```

### Percentual
```
Campo: % Desconto (medida)
Rótulo: % Desconto Médio
Formato: percent
Ícone: fa-percentage
Cor: warning
```

---

## 📊 Tipos de Gráfico

| Tipo | Uso | Exemplo |
|------|-----|---------|
| **bar** | Comparações | Vendas por vendedor |
| **line** | Tendências | Evolução mensal |
| **pie** | Distribuição % | Participação por grupo |
| **doughnut** | Distribuição % | Similar ao pizza |

---

## 🔍 Tipos de Filtro

| Tipo | Uso | Exemplo |
|------|-----|---------|
| **text** | Texto livre | Nome do vendedor |
| **number** | Numérico | Código do cliente |
| **date** | Data completa | 2025-11-05 |
| **year** | Ano | 2025 |
| **month** | Mês (1-12) | 11 |

---

## 🎯 Atalhos

| Ação | Como Fazer |
|------|------------|
| **Adicionar campo rapidamente** | Arrastar do painel direito |
| **Ver campos disponíveis** | Painel direito (após selecionar relatório) |
| **Voltar para etapa 1** | Botão "Voltar" |
| **Testar fórmula** | Criar medida → Salvar → Ver resultado |

---

## ⚠️ Avisos Importantes

### ✅ FAÇA
- Use nomes descritivos
- Teste fórmulas antes de salvar
- Limite KPIs a 4-6 por dashboard
- Use cores para destacar

### ❌ NÃO FAÇA
- Não use caracteres < > & | $ ` \
- Não esqueça de fechar parênteses
- Não misture formatos (ex: currency em %)
- Não crie muitos gráficos (máx. 6)

---

## 🐛 Erros Comuns

| Erro | Causa | Solução |
|------|-------|---------|
| "Fórmula inválida!" | Sintaxe errada | Ver exemplos de fórmulas |
| "Campo não encontrado" | Nome errado | Verificar no painel de campos |
| KPI mostra 0 | Agregação errada | Verificar agregação |
| Gráfico não aparece | Config incompleta | Preencher todos os campos |

---

## 📚 Onde Encontrar Mais

| Documento | Conteúdo |
|-----------|----------|
| `GUIA_DASHBOARD_BUILDER.md` | 📖 Guia completo passo a passo |
| `DASHBOARD_FORMULAS.md` | 📐 Todas as fórmulas disponíveis |
| `EXEMPLO_DASHBOARD_RMVENDAS.md` | 🎯 Exemplo prático completo |
| `RESUMO_PROJETO_DASHBOARD.md` | 📋 Visão geral do projeto |

---

## 🎯 Dashboard de Exemplo

**Dashboard de Vendas - RMVendas** (já criado!)

```
Menu → Relatórios → Meus Dashboards → "Dashboard de Vendas - RMVendas"
```

**Contém:**
- ✅ 4 Medidas Calculadas
- ✅ 6 KPIs
- ✅ 4 Filtros
- ✅ 4 Gráficos

**Use como referência!**

---

## 🆘 Precisa de Ajuda?

1. **Leia os guias** em `docs/`
2. **Veja o exemplo** Dashboard de Vendas - RMVendas
3. **Teste com dados** reais
4. **Contate** o administrador

---

**💡 Dica:** Salve este guia para consulta rápida!

**🎉 Bom trabalho!** 🚀

