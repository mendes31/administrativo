# ⚡ Guia Rápido - Editar Dashboards

## 🎯 Resumo em 3 Formas

| O que Editar | Como Fazer | Tempo |
|--------------|------------|-------|
| **Nome, Descrição** | Editar → Aba "Informações" → Salvar | 30s |
| **Medidas, KPIs, Filtros, Gráficos** | Editar → Abas correspondentes → ✏️ → Salvar | 2min |
| **Adicionar Novos Relatórios** | Criar Novo → Selecionar vários → Configurar | 5min |

---

## 📝 Como Editar uma Medida (Rápido)

```
1. Dashboard → "Editar" (botão amarelo)
2. Clicar na aba "Medidas"
3. Clicar no botão ✏️ ao lado da medida
4. Prompts aparecerão - modifique conforme necessário
5. Rolar até o final → "Salvar Alterações"
6. ✅ Pronto!
```

**Exemplo Prático:**
```
Ajustar "Margem %" de:
  ([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100
Para:
  ([Total c/ Desc] - [Custo_+_Impostos]) / [Total c/ Desc] * 100
```

---

## ➕ Como Adicionar Nova Medida

```
1. Editar → Aba "Medidas"
2. Clicar em "Adicionar Nova Medida"
3. Preencher:
   Nome: ROI %
   Fórmula: ([Vendas] - [Custos]) / [Custos] * 100
   Formato: percent
4. Salvar Alterações
5. ✅ Nova medida aparece no dashboard!
```

---

## 🎨 Como Editar um KPI

```
1. Editar → Aba "KPIs"
2. Clicar no botão ✏️ do KPI
3. Modificar:
   - Campo, Rótulo, Agregação, Formato, Ícone, Cor
4. Salvar Alterações
```

---

## 🔍 Como Editar um Filtro

```
1. Editar → Aba "Filtros"
2. Clicar no botão ✏️ do filtro
3. Modificar:
   - Campo, Rótulo, Tipo, Obrigatório, Valor Padrão
4. Salvar Alterações
```

---

## 📊 Como Editar um Gráfico

```
1. Editar → Aba "Gráficos"
2. Clicar no botão ✏️ do gráfico
3. Modificar:
   - Tipo, Agrupar por, Valor, Agregação, Título
4. Salvar Alterações
```

---

## 🔄 Como Adicionar Insights de Novos Relatórios

```
Dashboard atual: Vendas (1 relatório)
Quer adicionar: Devoluções

Solução:
1. Criar Novo Dashboard
2. Selecionar:
   ✅ RMVendas
   ✅ Devoluções de Venda
3. Criar medidas combinadas:
   - Taxa Devolução = Devoluções / Vendas * 100
4. Salvar
```

---

## 🚀 Atalhos

| Ação | Atalho |
|------|--------|
| **Edição rápida** | Dashboard → Editar |
| **Editar medida** | Aba Medidas → ✏️ |
| **Adicionar KPI** | Aba KPIs → ➕ |
| **Duplicar** | Dashboard → Duplicar |
| **Multi-relatórios** | Criar Novo → Selecionar vários |

---

## ⚠️ Importante

### **Não dá para editar:**
- ❌ Relatório base (após criado)
- ❌ Adicionar mais relatórios (precisa criar novo)

### **Dá para editar:**
- ✅ Nome, descrição, categoria
- ✅ Medidas (✏️ editar, ➕ adicionar, 🗑️ remover)
- ✅ KPIs (✏️ editar, ➕ adicionar, 🗑️ remover)
- ✅ Filtros (✏️ editar, ➕ adicionar, 🗑️ remover)
- ✅ Gráficos (✏️ editar, ➕ adicionar, 🗑️ remover)

---

## 📚 Documentação Completa

- `COMO_EDITAR_DASHBOARDS.md` - Guia detalhado
- `DASHBOARD_FORMULAS.md` - Referência de fórmulas
- `GUIA_DASHBOARD_BUILDER.md` - Criar dashboards

---

**💡 Dica:** Leia `COMO_EDITAR_DASHBOARDS.md` para exemplos completos!

**🎉 Bom trabalho!** 🚀

