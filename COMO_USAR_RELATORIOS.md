# 📊 COMO USAR O SISTEMA DE RELATÓRIOS DINÂMICOS

## 🎯 GUIA RÁPIDO

### Passo 1: Acessar o Construtor
```
http://localhost/administrativo/dynamic-report-builder
```

### Passo 2: Selecionar Fonte de Dados
- **Locais:** Usuários, Treinamentos, CRM, Financeiro
- **SAP B1:** Clientes, Notas Fiscais, Pedidos, Estoque

### Passo 3: Adicionar Campos
- Clicar em "+ Adicionar Campo"
- Selecionar campo
- Escolher agregação (opcional): COUNT, SUM, AVG, MIN, MAX
- Definir alias (opcional)

### Passo 4: Adicionar Filtros (Opcional)
- Clicar em "+ Adicionar Filtro"
- Selecionar campo
- Escolher operador: =, !=, >, <, LIKE, IN, BETWEEN
- Informar valor

### Passo 5: Configurar Visualização
- Tipo: Tabela ou Gráfico (Barras, Linhas, Pizza, etc)
- Atualização automática (opcional)

### Passo 6: Visualizar Prévia
- Clicar em "Visualizar Prévia"
- **Dados aparecem em tempo real!** ⚡

### Passo 7: Salvar
- Dar um nome
- Escolher categoria
- Marcar "Compartilhar" se quiser que outros vejam
- Salvar

---

## 💡 EXEMPLOS PRÁTICOS

### Exemplo 1: Vendas do Mês (SAP B1)
```
Fonte: SAP B1 - Notas Fiscais (OINV)
Campos:
  - DocDate → SEM agregação → Alias: "Data"
  - DocTotal → SUM → Alias: "Total Vendido"
Filtros:
  - MONTH(DocDate) = MONTH(CURRENT_DATE)
Agrupar: DocDate
Ordenar: DocDate DESC
Visualização: Gráfico de Linhas
```

### Exemplo 2: Treinamentos por Status
```
Fonte: Status de Treinamentos
Campos:
  - status → SEM agregação → Alias: "Status"
  - id → COUNT → Alias: "Quantidade"
Agrupar: status
Visualização: Gráfico de Pizza
```

### Exemplo 3: Top 10 Clientes (SAP B1)
```
Fonte: SAP B1 - Clientes (OCRD)
Campos:
  - CardName → SEM agregação → Alias: "Cliente"
  - Balance → SUM → Alias: "Saldo"
Filtros:
  - CardType = 'C'
Agrupar: CardName
Ordenar: Balance DESC
Limite: 10
Visualização: Gráfico de Barras Horizontais
```

---

## ⚡ ATUALIZAÇÃO EM TEMPO REAL

Para relatórios que precisam de dados sempre atualizados:

1. Marcar "Atualização Automática"
2. Definir intervalo (ex: 30 segundos)
3. Salvar

O relatório será atualizado automaticamente! 🔄

---

## 🌟 DICAS PRO

### Agregações
- **COUNT** - Contar registros
- **SUM** - Somar valores
- **AVG** - Média
- **MIN** - Menor valor
- **MAX** - Maior valor

### Operadores de Filtro
- **=** - Igual
- **!=** ou **<>** - Diferente
- **>**, **<**, **>=**, **<=** - Comparações
- **LIKE** - Contém texto (use % antes/depois)
- **IN** - Está na lista
- **BETWEEN** - Entre dois valores
- **IS NULL** / **IS NOT NULL** - Vazio/Preenchido

### Performance
- Use filtros sempre que possível
- Evite SELECT * em tabelas grandes
- Use agregações quando precisar de totalizações

---

## 📤 EXPORTAR DADOS

(Em implementação)
- Excel
- PDF
- CSV

---

✅ **Divirta-se criando relatórios!** 🎉

