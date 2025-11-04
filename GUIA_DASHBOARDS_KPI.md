# 📊 Guia Completo: Dashboards de KPI

## 🎯 Visão Geral

O módulo **Dashboards de KPI** permite criar painéis personalizados com indicadores de desempenho (KPIs) que consomem dados dos **Relatórios Dinâmicos** cadastrados no sistema.

---

## ✨ Funcionalidades

### 1. **Dashboards Personalizáveis**
- Criar múltiplos dashboards
- Layout configurável (Grid, Flexível, Personalizado)
- Atualização automática em tempo real
- Visibilidade pública ou privada

### 2. **Widgets Diversos**
- **Número**: Exibe um valor único (vendas, clientes, etc.)
- **Gráfico de Barras**: Comparação de valores
- **Gráfico de Linhas**: Tendências ao longo do tempo
- **Gráfico de Pizza**: Proporções
- **Gráfico Rosca (Doughnut)**: Similar ao pizza, com centro vazio
- **Tabela**: Exibição tabular de dados
- **Medidor (Gauge)**: Progresso em relação a uma meta

### 3. **Integração com Relatórios Dinâmicos**
- Vincula widgets a queries do módulo de Relatórios Dinâmicos
- Suporta MySQL local e SAP B1 HANA
- Formatação automática de valores (moeda, porcentagem, número)

### 4. **Atualização em Tempo Real**
- Configurar intervalo de atualização (em segundos)
- Widgets atualizam automaticamente sem recarregar a página
- Indicação visual de carregamento

---

## 🚀 Como Usar

### **PASSO 1: Executar Migrations**

Execute a migration para criar as tabelas necessárias:

```bash
cd C:\wamp64\www\administrativo
vendor\bin\phinx migrate -e development
```

**Tabelas criadas:**
- `adms_kpi_dashboards` - Dashboards de KPI
- `adms_kpi_widgets` - Widgets/Indicadores
- `adms_kpi_dashboard_permissions` - Permissões de visualização

---

### **PASSO 2: Executar Seeds**

Adicionar páginas ao sistema:

```bash
vendor\bin\phinx seed:run -s AddKpiDashboardPages
```

**Páginas criadas:**
- `list-kpi-dashboards` - Listar dashboards
- `view-kpi-dashboard` - Visualizar dashboard
- `create-kpi-dashboard` - Criar dashboard
- `update-kpi-dashboard` - Atualizar dashboard
- `delete-kpi-dashboard` - Deletar dashboard
- `get-kpi-widget-data` - API para buscar dados dos widgets

---

### **PASSO 3: Configurar Permissões**

1. Acesse: **Menu → Níveis de Acesso → Permissões**
2. Selecione o nível de acesso desejado (ex: Manager, Diretor)
3. Marque as permissões do grupo **"Dashboards KPI"**:
   - ✅ list-kpi-dashboards
   - ✅ view-kpi-dashboard
   - ✅ create-kpi-dashboard
   - ✅ get-kpi-widget-data
4. Salvar

---

### **PASSO 4: Adicionar Menu**

Editar `app/adms/Views/partials/menu.php` e adicionar:

```php
<!-- Dashboards de KPI -->
<?php if ($this->hasPermission('list-kpi-dashboards')): ?>
<a class="nav-link collapsed" href="#" data-bs-toggle="collapse" 
   data-bs-target="#collapseDashboards" aria-expanded="false" aria-controls="collapseDashboards">
    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
    Dashboards KPI
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseDashboards" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a class="nav-link" href="<?= $_ENV['URL_ADM'] ?>list-kpi-dashboards">
            <i class="fas fa-list"></i> Meus Dashboards
        </a>
        <?php if ($this->hasPermission('create-kpi-dashboard')): ?>
        <a class="nav-link" href="<?= $_ENV['URL_ADM'] ?>create-kpi-dashboard">
            <i class="fas fa-plus"></i> Criar Dashboard
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>
```

---

## 📝 Criando um Dashboard

### **1. Criar Relatórios Dinâmicos**

Primeiro, crie os relatórios que serão usados nos widgets:

**Exemplo 1: Total de Vendas (SAP B1)**
```sql
SELECT TO_DECIMAL(SUM("LineTotal"), 15, 2) AS "ValorTotal"
FROM "INV1" d
INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE h."CANCELED" = 'N' AND h."DocStatus" = 'O'
```

**Exemplo 2: Vendas por Mês (SAP B1)**
```sql
SELECT 
    TO_VARCHAR(h."DocDate", 'YYYY-MM') AS "Mes",
    TO_DECIMAL(SUM(d."LineTotal"), 15, 2) AS "Valor"
FROM "INV1" d
INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE h."CANCELED" = 'N'
GROUP BY TO_VARCHAR(h."DocDate", 'YYYY-MM')
ORDER BY "Mes" DESC
LIMIT 12
```

**Exemplo 3: Top 5 Clientes (MySQL Local)**
```sql
SELECT 
    name AS Cliente, 
    total_purchases AS TotalCompras
FROM customers
WHERE active = 1
ORDER BY total_purchases DESC
LIMIT 5
```

---

### **2. Criar o Dashboard**

1. Acesse: **Dashboards KPI → Criar Dashboard**
2. Preencha:
   - **Nome**: "Dashboard Comercial"
   - **Descrição**: "Indicadores de vendas e performance"
   - **Layout**: Grid
   - **Intervalo de Atualização**: 60 (segundos)
   - **Dashboard público**: ☑️ (se quiser que todos vejam)

---

### **3. Adicionar Widgets**

Clique em **"Adicionar Widget"** e configure:

#### **Widget 1: Total de Vendas (Número)**
- **Título**: Total de Vendas
- **Relatório Vinculado**: (selecione o relatório "Total de Vendas")
- **Tipo de Widget**: Número
- **Tamanho**: Médio
- **Esquema de Cores**: Verde (Success)
- **Formato do Valor**: Moeda (R$)
- **Ícone**: `fas fa-dollar-sign`
- **Valor Meta**: 100000 (opcional)

#### **Widget 2: Vendas por Mês (Gráfico de Linhas)**
- **Título**: Evolução de Vendas
- **Relatório Vinculado**: (selecione o relatório "Vendas por Mês")
- **Tipo de Widget**: Gráfico de Linhas
- **Tamanho**: Grande
- **Esquema de Cores**: Azul (Primary)
- **Ícone**: `fas fa-chart-line`

#### **Widget 3: Top 5 Clientes (Gráfico de Barras)**
- **Título**: Principais Clientes
- **Relatório Vinculado**: (selecione o relatório "Top 5 Clientes")
- **Tipo de Widget**: Gráfico de Barras
- **Tamanho**: Médio
- **Esquema de Cores**: Ciano (Info)
- **Ícone**: `fas fa-users`

---

### **4. Salvar e Visualizar**

1. Clique em **"Salvar Dashboard"**
2. Você será redirecionado para a visualização do dashboard
3. Os widgets carregarão automaticamente os dados
4. Se configurou atualização automática, os dados serão atualizados periodicamente

---

## 🎨 Tipos de Widget e Configurações

### **1. Widget Número**
- **Ideal para**: KPIs únicos (total vendas, número de clientes, meta atingida)
- **Configurações**:
  - Formato: Número, Moeda, Porcentagem, Texto
  - Prefixo/Sufixo: Adicionar texto antes/depois do valor
  - Valor Meta: Exibe barra de progresso

### **2. Gráficos (Barras, Linhas, Pizza, Rosca)**
- **Ideal para**: Comparações, tendências, proporções
- **Requisitos da Query**:
  - **Coluna 1**: Labels (nomes, categorias, períodos)
  - **Coluna 2**: Valores numéricos

### **3. Tabela**
- **Ideal para**: Listagens detalhadas
- **Configurações**: Exibe todas as colunas retornadas pela query

### **4. Medidor (Gauge)**
- **Ideal para**: Progresso em relação a uma meta
- **Configurações**: Similar ao Widget Número, mas com visualização de medidor

---

## 🎯 Exemplos de Uso

### **Exemplo 1: Dashboard Financeiro**
```
┌─────────────────┬─────────────────┬─────────────────┐
│ Total Receitas  │ Total Despesas  │ Lucro Líquido   │
│ R$ 1.500.000,00 │ R$ 800.000,00   │ R$ 700.000,00   │
│ 📈 +15% vs mês  │ 📉 -5% vs mês   │ 💰 +25% vs mês  │
└─────────────────┴─────────────────┴─────────────────┘
┌─────────────────────────────────────────────────────┐
│ Gráfico: Evolução Mensal (Receitas vs Despesas)    │
│ [Gráfico de Linhas]                                 │
└─────────────────────────────────────────────────────┘
```

### **Exemplo 2: Dashboard Operacional**
```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│ Pedidos Hoje│ Em Produção │ Aguardando  │ Finalizados │
│     45      │     12      │      8      │     25      │
└─────────────┴─────────────┴─────────────┴─────────────┘
┌───────────────────────────────────────────────────────┐
│ Gráfico: Distribuição por Status                     │
│ [Gráfico de Pizza]                                    │
└───────────────────────────────────────────────────────┘
```

---

## 🔧 Troubleshooting

### **Problema: Widget mostra "Erro ao carregar"**

**Solução 1**: Verifique se o relatório vinculado está funcionando
1. Acesse: **Relatórios Dinâmicos → Listar**
2. Abra o relatório vinculado ao widget
3. Clique em "Visualizar Prévia"
4. Se houver erro, corrija a query

**Solução 2**: Verifique o console do navegador
1. Pressione F12 (DevTools)
2. Aba "Console"
3. Procure por erros em vermelho

---

### **Problema: Dashboard não atualiza automaticamente**

**Solução**: Verifique o intervalo de atualização
1. Edite o dashboard
2. Certifique-se que "Intervalo de Atualização" > 0
3. Recomendado: 30 a 120 segundos

---

### **Problema: Gráfico não aparece**

**Solução**: Verifique o formato dos dados
- Gráficos precisam de **2 colunas**: Labels e Valores
- Exemplo correto:
  ```
  Mês       | Valor
  ----------|-------
  2025-01   | 15000
  2025-02   | 18000
  ```

---

## 📊 Melhores Práticas

### **1. Organização**
- ✅ Crie dashboards temáticos (Financeiro, Vendas, Operacional)
- ✅ Use nomes descritivos para dashboards e widgets
- ✅ Agrupe widgets relacionados

### **2. Performance**
- ✅ Use queries otimizadas (índices, LIMIT)
- ✅ Configure intervalo de atualização razoável (60s+)
- ✅ Evite queries muito complexas

### **3. Visualização**
- ✅ Use cores consistentes (verde=positivo, vermelho=negativo)
- ✅ Escolha o tipo de widget adequado aos dados
- ✅ Adicione ícones para melhor identificação
- ✅ Configure metas para widgets numéricos

### **4. Segurança**
- ✅ Use dashboards privados para dados sensíveis
- ✅ Configure permissões por nível de acesso
- ✅ Revise queries antes de publicar

---

## 🎓 Tutorial em Vídeo

### **Cenário: Dashboard de Vendas SAP B1**

**1. Criar Relatórios Base (5 min)**
- Total de Vendas do Mês
- Top 10 Produtos
- Vendas por Vendedor
- Evolução Semanal

**2. Criar Dashboard (3 min)**
- Nome: "Dashboard de Vendas - SAP B1"
- Layout: Grid
- Atualização: 120 segundos

**3. Configurar Widgets (10 min)**
- Widget 1: Total do Mês (Número, R$)
- Widget 2: Top 10 Produtos (Barras)
- Widget 3: Por Vendedor (Pizza)
- Widget 4: Evolução Semanal (Linhas)

**4. Publicar e Compartilhar (2 min)**
- Marcar como público
- Testar atualização automática
- Compartilhar link com equipe

---

## 📚 Referências

- **Relatórios Dinâmicos**: `GUIA_IMPLEMENTACAO_RELATORIOS_DINAMICOS.md`
- **Conexão SAP B1**: `INSTALACAO_HDBODBC_COMPLETA.md`
- **Chart.js**: https://www.chartjs.org/docs/latest/
- **FontAwesome Icons**: https://fontawesome.com/icons

---

## 🆘 Suporte

Em caso de dúvidas ou problemas:
1. Verifique os logs: `app/logs/`
2. Console do navegador (F12)
3. Documentação dos Relatórios Dinâmicos

---

**🎉 Pronto! Seu sistema de Dashboards de KPI está configurado e funcionando!**

