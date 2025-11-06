# 🎯 Como Editar Dashboards - Guia Completo

## 📋 Você Tem 2 Opções de Edição

### **Opção 1: Edição Rápida (Informações Básicas)** ⚡
Para mudar apenas nome, descrição, categoria, visibilidade.

### **Opção 2: Edição Avançada (Medidas, KPIs, Filtros, Gráficos)** 🔧
Para alterar/adicionar medidas, KPIs, filtros, gráficos.

---

## ⚡ Opção 1: Edição Rápida

### **Quando Usar:**
- Mudar nome do dashboard
- Ajustar descrição
- Mudar categoria
- Tornar público/privado

### **Como Fazer:**
```
1. Acessar o dashboard
2. Clicar em "Editar" (botão amarelo)
3. Mudar as informações básicas
4. Clicar em "Salvar Alterações"
```

**Limitação:** NÃO edita medidas, KPIs, filtros, gráficos.

---

## 🔧 Opção 2: Edição Avançada (Medidas, KPIs, etc.)

### **Você Tem 2 Formas de Fazer:**

---

### **Forma A: Editar Diretamente** (Nova Funcionalidade)

Agora você pode editar medidas, KPIs, filtros e gráficos **DIRETAMENTE na tela de edição**!

#### **Passo a Passo:**

1. **Acessar Edição:**
   ```
   Dashboard → Botão "Editar"
   ```

2. **Usar as Abas:**
   
   **Aba "Medidas":**
   - Ver lista de medidas atuais
   - Clicar em ✏️ para **EDITAR** uma medida
   - Clicar em 🗑️ para **REMOVER** uma medida
   - Clicar em **"Adicionar Nova Medida"**
   
   **Como Editar uma Medida:**
   ```
   1. Clicar no botão ✏️ (editar) da medida
   2. Prompt aparecerá com valor atual
   3. Modificar:
      - Nome: Ticket Médio
      - Fórmula: [Total c/ Desc] / COUNT([NumDoc])
      - Formato: currency
   4. Confirmar
   5. ✅ Medida atualizada!
   ```
   
   **Como Adicionar Nova Medida:**
   ```
   1. Clicar em "Adicionar Nova Medida"
   2. Preencher:
      - Nome: ROI %
      - Fórmula: ([Total c/ Desc] - [Custo_Total_Item]) / [Custo_Total_Item] * 100
      - Formato: percent
   3. ✅ Medida adicionada!
   ```

   **Aba "KPIs":**
   - Ver lista de KPIs atuais
   - Clicar em ✏️ para **EDITAR**
   - Clicar em 🗑️ para **REMOVER**
   - Clicar em **"Adicionar Novo KPI"**
   
   **Como Editar um KPI:**
   ```
   1. Clicar no botão ✏️ do KPI
   2. Modificar:
      - Campo: Total c/ Desc
      - Rótulo: Faturamento Total
      - Agregação: sum
      - Formato: currency
      - Ícone: fa-dollar-sign
      - Cor: success
   3. ✅ KPI atualizado!
   ```

   **Aba "Filtros":**
   - Editar filtros existentes
   - Adicionar novos filtros
   - Configurar valor padrão
   - Marcar como obrigatório
   
   **Como Editar um Filtro:**
   ```
   1. Clicar no botão ✏️ do filtro
   2. Modificar:
      - Campo: nomeVendedor
      - Rótulo: Vendedor
      - Tipo: text
      - Obrigatório: Não
      - Valor Padrão: (vazio)
   3. ✅ Filtro atualizado!
   ```

   **Aba "Gráficos":**
   - Editar gráficos existentes
   - Adicionar novos gráficos
   - Mudar tipo, agregação, títulos
   
   **Como Editar um Gráfico:**
   ```
   1. Clicar no botão ✏️ do gráfico
   2. Modificar:
      - Tipo: bar
      - Agrupar por: nomeVendedor
      - Campo de Valor: Total c/ Desc
      - Agregação: sum
      - Título: Faturamento por Vendedor
   3. ✅ Gráfico atualizado!
   ```

3. **Salvar:**
   - Após fazer todas as mudanças nas abas
   - Clicar em **"Salvar Alterações"** (botão no final)
   - ✅ Dashboard atualizado!

---

### **Forma B: Duplicar Dashboard** (Para Grandes Mudanças)

Use quando quiser fazer mudanças grandes ou adicionar novos relatórios.

#### **Passo a Passo:**

1. **Duplicar:**
   ```
   Dashboard → Botão "Duplicar"
   ```
   - Cria uma cópia com todas as configurações
   - Nome: "Dashboard de Vendas - RMVendas (Cópia)"
   - Status: Privado (só você vê)

2. **Editar a Cópia:**
   - Sistema redireciona automaticamente para edição
   - Usar as abas para modificar tudo
   - Ou criar novo dashboard selecionando mais relatórios

3. **Publicar:**
   - Quando estiver satisfeito, marque como "Público"
   - Salve
   - (Opcional) Exclua o dashboard original

---

## 📊 Como Adicionar Novos Relatórios ao Dashboard

### **Cenário: Quero Combinar Vendas + Devoluções**

**Problema:** Um dashboard criado só tem 1 relatório (RMVendas). Não dá para adicionar mais relatórios depois.

**Solução:** Criar novo dashboard com múltiplos relatórios.

#### **Passo a Passo:**

1. **Ir para Criar Novo Dashboard:**
   ```
   Menu → Relatórios → Meus Dashboards → Criar Novo Dashboard
   ```

2. **Etapa 1: Selecionar MÚLTIPLOS Relatórios:**
   ```
   ✅ RMVendas (vendas)
   ✅ Devoluções de Venda (devoluções)
   ✅ [FILTRO] Itens (produtos)
   
   Clicar: "Próximo"
   ```

3. **Etapa 2: Usar Campos de Todos:**
   
   **Painel Direito mostrará:**
   ```
   📊 RMVendas (40+ campos)
   📊 Devoluções de Venda (30+ campos)
   📊 [FILTRO] Itens (4 campos)
   ```
   
   **Criar Medidas Combinadas:**
   ```
   Nome: Taxa de Devolução
   Fórmula: SUM(Devoluções[Total c/ Desc]) / SUM(RMVendas[Total c/ Desc]) * 100
   Formato: percent
   ```
   
   **Criar KPIs:**
   ```
   KPI 1: Vendas Totais (de RMVendas)
   KPI 2: Devoluções Totais (de Devoluções)
   KPI 3: Vendas Líquidas (medida: Vendas - Devoluções)
   KPI 4: Taxa de Devolução (medida criada acima)
   ```
   
   **Criar Gráficos:**
   ```
   Gráfico 1: Vendas por Vendedor (de RMVendas)
   Gráfico 2: Devoluções por Vendedor (de Devoluções)
   Gráfico 3: Comparativo Vendas vs Devoluções
   ```

4. **Salvar:**
   - Clicar em "Criar Dashboard"
   - ✅ Dashboard com múltiplos relatórios criado!

---

## 🎓 Exemplos Práticos

### **Exemplo 1: Ajustar Fórmula de uma Medida**

**Cenário:** A medida "Margem %" está calculando errado.

**Solução:**
```
1. Dashboard → Editar
2. Aba "Medidas"
3. Clicar em ✏️ ao lado de "Margem %"
4. Modificar fórmula de:
   ([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100
   Para:
   ([Total c/ Desc] - [Custo_+_Impostos]) / [Total c/ Desc] * 100
5. Confirmar
6. Salvar Alterações
7. ✅ Medida corrigida!
```

---

### **Exemplo 2: Adicionar Novo KPI**

**Cenário:** Quero ver o faturamento líquido (vendas - devoluções).

**Passo 1: Criar Medida Calculada**
```
1. Dashboard → Editar
2. Aba "Medidas"
3. Clicar em "Adicionar Nova Medida"
4. Preencher:
   - Nome: Faturamento Líquido
   - Fórmula: [Total c/ Desc] - [Desc Rodapé]
   - Formato: currency
5. Confirmar
```

**Passo 2: Criar KPI com a Medida**
```
1. Aba "KPIs"
2. Clicar em "Adicionar Novo KPI"
3. Preencher:
   - Campo: Faturamento Líquido
   - Rótulo: Faturamento Líquido
   - Agregação: (vazio, pois é medida calculada)
   - Formato: currency
   - Ícone: fa-money-bill-wave
   - Cor: success
4. Confirmar
5. Salvar Alterações
6. ✅ Novo KPI aparecerá no dashboard!
```

---

### **Exemplo 3: Adicionar Filtro de Produto**

**Cenário:** Quero filtrar vendas por produto específico.

**Solução:**
```
1. Dashboard → Editar
2. Aba "Filtros"
3. Clicar em "Adicionar Novo Filtro"
4. Preencher:
   - Campo: nomeItem
   - Rótulo: Produto
   - Tipo: text
   - Obrigatório: Não
   - Valor Padrão: (vazio)
5. Confirmar
6. Salvar Alterações
7. ✅ Novo filtro "Produto" aparecerá no dashboard!
```

---

### **Exemplo 4: Mudar Tipo de Gráfico**

**Cenário:** Quero mudar o gráfico de barras para pizza.

**Solução:**
```
1. Dashboard → Editar
2. Aba "Gráficos"
3. Clicar em ✏️ no gráfico desejado
4. Modificar:
   - Tipo: De "bar" para "pie"
5. Manter outros campos iguais
6. Confirmar
7. Salvar Alterações
8. ✅ Gráfico agora é pizza!
```

---

## 🚀 Workflow Completo

### **Para Edições Simples (Nome, KPI, etc.):**
```
1. Visualizar Dashboard
        ↓
2. Clicar em "Editar"
        ↓
3. Usar abas para modificar
   - Informações Básicas
   - Medidas (✏️ editar, 🗑️ remover, ➕ adicionar)
   - KPIs (✏️ editar, 🗑️ remover, ➕ adicionar)
   - Filtros (✏️ editar, 🗑️ remover, ➕ adicionar)
   - Gráficos (✏️ editar, 🗑️ remover, ➕ adicionar)
        ↓
4. Salvar Alterações
        ↓
5. ✅ Dashboard atualizado!
```

### **Para Adicionar Novos Relatórios (Insights):**
```
1. Visualizar Dashboard
        ↓
2. Clicar em "Duplicar" (cria cópia)
        ↓
3. OU ir direto: Criar Novo Dashboard
        ↓
4. Selecionar MÚLTIPLOS relatórios:
   ✅ Relatório original
   ✅ Novos relatórios
        ↓
5. Configurar medidas/KPIs/gráficos combinando dados
        ↓
6. Salvar
        ↓
7. ✅ Dashboard com múltiplos insights!
```

---

## 🎯 Botões Disponíveis no Dashboard

| Botão | Função | Quando Aparece |
|-------|--------|----------------|
| **Editar** (Amarelo) | Editar todas as configurações | Apenas para o criador |
| **Duplicar** (Amarelo outline) | Criar cópia editável | Apenas para o criador |
| **Ver Relatório Original** (Azul) | Ver relatório base | Sempre |
| **Voltar** (Cinza) | Voltar para lista | Sempre |

---

## 📐 Como Selecionar e Ajustar uma Medida

### **Método 1: Edição Inline (Rápido)**

```
1. Dashboard → Editar
2. Aba "Medidas"
3. Encontre a medida (ex: "Ticket Médio")
4. Clicar no botão ✏️ (editar) ao lado
5. Prompts aparecerão:
   ┌─────────────────────────────────┐
   │ Nome da Medida:                 │
   │ [Ticket Médio____________]      │ ← Modificar aqui
   │         [OK] [Cancelar]         │
   └─────────────────────────────────┘
   
   ┌─────────────────────────────────┐
   │ Fórmula:                        │
   │ [[Total c/ Desc] / COUNT(...]]  │ ← Modificar aqui
   │         [OK] [Cancelar]         │
   └─────────────────────────────────┘
   
   ┌─────────────────────────────────┐
   │ Formato:                        │
   │ [currency_____________]         │ ← Modificar aqui
   │         [OK] [Cancelar]         │
   └─────────────────────────────────┘
6. Clicar em "Salvar Alterações" (botão no final da página)
7. ✅ Medida ajustada e salva!
```

### **Método 2: Remover + Adicionar Nova**

```
1. Clicar em 🗑️ (remover) na medida antiga
2. Confirmar remoção
3. Clicar em "Adicionar Nova Medida"
4. Preencher com valores corretos
5. Salvar Alterações
```

---

## 🆕 Como Adicionar Insights de Novos Relatórios

### **Cenário Prático:**

Você tem um **Dashboard de Vendas** e quer adicionar **análise de devoluções** para comparar.

#### **Solução:**

1. **Criar Novo Dashboard Multi-Relatórios:**
   ```
   Menu → Relatórios → Meus Dashboards → Criar Novo
   ```

2. **Selecionar Relatórios:**
   ```
   ✅ RMVendas (dados de vendas)
   ✅ Devoluções de Venda (dados de devoluções)
   
   Clicar: Próximo
   ```

3. **Configurar Medidas Combinadas:**
   
   **Medida 1: Taxa de Devolução**
   ```
   Nome: Taxa de Devolução
   Fórmula: SUM([Qtde da Devolução]) / SUM([Qtde de Venda]) * 100
   Formato: percent
   ```
   
   **Medida 2: Valor Devolvido**
   ```
   Nome: Valor Devolvido
   Fórmula: SUM([Total c/ Desc]) (do relatório Devoluções)
   Formato: currency
   ```
   
   **Medida 3: Vendas Líquidas**
   ```
   Nome: Vendas Líquidas
   Fórmula: [Faturamento Total] - [Valor Devolvido]
   Formato: currency
   ```

4. **Criar KPIs:**
   ```
   KPI 1: Faturamento Bruto (de RMVendas)
   KPI 2: Devoluções (de Devoluções)
   KPI 3: Faturamento Líquido (medida calculada)
   KPI 4: Taxa de Devolução (medida calculada)
   ```

5. **Criar Gráficos Comparativos:**
   ```
   Gráfico 1: Vendas por Vendedor (RMVendas)
   Gráfico 2: Devoluções por Vendedor (Devoluções)
   Gráfico 3: Vendas vs Devoluções (combinado)
   ```

6. **Salvar:**
   - Clicar em "Criar Dashboard"
   - Nome: "Dashboard Vendas + Devoluções"
   - ✅ Dashboard com múltiplos insights criado!

---

## 💡 Dicas e Boas Práticas

### ✅ **FAÇA:**

1. **Teste Fórmulas:**
   - Crie a medida
   - Salve
   - Visualize o dashboard
   - Verifique se o valor está correto

2. **Nomeie Claramente:**
   ```
   ✅ Bom: "Faturamento Líquido (Vendas - Devoluções)"
   ❌ Ruim: "Medida 1"
   ```

3. **Use Cores Significativas:**
   ```
   ✅ Verde (success) → Valores positivos (faturamento)
   ✅ Vermelho (danger) → Custos, devoluções
   ✅ Amarelo (warning) → Alertas, margens
   ✅ Azul (info) → Informações gerais
   ```

4. **Organize por Importância:**
   - KPIs mais importantes primeiro
   - Gráficos principais no topo

5. **Documente Fórmulas Complexas:**
   - Use a descrição do dashboard para explicar medidas complexas

### ❌ **NÃO FAÇA:**

1. Não crie medidas sem testar
2. Não use nomes genéricos
3. Não coloque muitos KPIs (máx. 6-8)
4. Não esqueça de salvar após editar!

---

## 🐛 Resolução de Problemas

### **Problema: Editei mas não salvou**
**Causa:** Esqueceu de clicar em "Salvar Alterações"  
**Solução:** Sempre clicar no botão de salvar no final!

### **Problema: Quero adicionar mais relatórios**
**Causa:** Dashboard já criado só tem 1 relatório  
**Solução:** Criar novo dashboard selecionando múltiplos relatórios

### **Problema: Medida não aparece no KPI**
**Causa:** Nome não corresponde exatamente  
**Solução:** Copiar e colar o nome exato da medida

### **Problema: Alteração não reflete no dashboard**
**Causa:** Cache do navegador  
**Solução:** Recarregar com Ctrl+F5

---

## 📚 Resumo de Funcionalidades

| Função | Como Fazer |
|--------|------------|
| **Editar Nome/Descrição** | Editar → Aba "Informações Básicas" |
| **Editar Medida** | Editar → Aba "Medidas" → ✏️ |
| **Adicionar Medida** | Editar → Aba "Medidas" → ➕ |
| **Editar KPI** | Editar → Aba "KPIs" → ✏️ |
| **Adicionar KPI** | Editar → Aba "KPIs" → ➕ |
| **Editar Filtro** | Editar → Aba "Filtros" → ✏️ |
| **Adicionar Filtro** | Editar → Aba "Filtros" → ➕ |
| **Editar Gráfico** | Editar → Aba "Gráficos" → ✏️ |
| **Adicionar Gráfico** | Editar → Aba "Gráficos" → ➕ |
| **Duplicar Dashboard** | Visualizar → Botão "Duplicar" |
| **Multi-Relatórios** | Criar Novo → Selecionar vários |

---

## ✅ Checklist de Edição

Antes de salvar, verifique:

- [ ] Nome do dashboard está claro e descritivo
- [ ] Todas as medidas têm fórmulas válidas
- [ ] KPIs têm agregação correta
- [ ] Filtros têm campos corretos
- [ ] Gráficos têm título descritivo
- [ ] Valor padrão configurado em filtros importantes
- [ ] Dashboard está público (se for para compartilhar)
- [ ] Testou as alterações (salvar → visualizar)

---

## 🎉 Resultado Final

### **Agora Você Pode:**

✅ **Editar diretamente** medidas, KPIs, filtros, gráficos  
✅ **Adicionar** novos elementos sem recriar do zero  
✅ **Remover** elementos desnecessários  
✅ **Duplicar** dashboards para criar variações  
✅ **Combinar** múltiplos relatórios para insights complexos  
✅ **Ajustar** fórmulas e configurações facilmente  

---

**🚀 Teste agora:**
```
1. Vá para o Dashboard de Vendas - RMVendas
2. Clique em "Editar"
3. Use as abas para modificar
4. Salve e veja as mudanças!
```

**💡 Dica:** Para adicionar insights de novos relatórios, use "Criar Novo Dashboard" e selecione múltiplos relatórios!

