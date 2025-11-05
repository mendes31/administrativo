# 📐 Fórmulas Calculadas - Estilo Power BI/DAX

O sistema de dashboards suporta fórmulas calculadas no estilo Power BI/DAX, permitindo criar medidas complexas com filtros e agregações.

## ✅ Funções Suportadas

- **SUM**: Soma de valores
- **AVG**: Média de valores
- **COUNT**: Contagem de registros
- **COUNT_DISTINCT**: Contagem de valores únicos
- **MIN**: Valor mínimo
- **MAX**: Valor máximo
- **CALCULATE**: Aplica filtros ao contexto de cálculo

## 📝 Formatos de Referência

### Campos Simples
```
[Campo]
```
Exemplo: `[TotalLinha]` - Soma todos os valores do campo "TotalLinha"

### Referências a Tabelas
```
TABELA[Campo]
```
Exemplo: `fRM_VENDAS[Total s/ Desc]` - Acessa o campo "Total s/ Desc" da tabela "fRM_VENDAS"

## 🔍 Filtros

### Filtro Simples
```
[Campo]="Valor"
```
Exemplo: `[Tipo Saida]="Venda"` - Filtra apenas registros onde "Tipo Saida" é igual a "Venda"

### Filtro com Tabela
```
TABELA[Campo]="Valor"
```
Exemplo: `fRM_VENDAS[Tipo Saida]="Venda"` - Filtra usando referência completa à tabela

### Múltiplos Filtros
```
TABELA[Campo1]="Valor1";TABELA[Campo2]="Valor2"
```
Use `;` (ponto e vírgula) para separar múltiplos filtros.

## 🎯 Exemplos Práticos

### Exemplo 1: Percentual de Desconto
```
[Total Desconto] / [TotalLinha] * 100
```
Calcula o percentual de desconto sobre o total.

### Exemplo 2: Percentual de Desconto com Filtro (Power BI)
```
CALCULATE([Total Desconto] / CALCULATE(SUM(fRM_VENDAS[Total s/ Desc]);fRM_VENDAS[Tipo Saida]="Venda"))
```
Esta fórmula:
1. Calcula `SUM` do campo "Total s/ Desc" da tabela "fRM_VENDAS"
2. Aplica filtro para `Tipo Saida = "Venda"`
3. Divide "Total Desconto" pelo resultado filtrado

### Exemplo 3: Soma com Filtro
```
CALCULATE(SUM([TotalLinha]);[Ano]=2025)
```
Soma o campo "TotalLinha" apenas para registros do ano 2025.

### Exemplo 4: Ticket Médio
```
SUM([TotalLinha]) / COUNT([NumDoc])
```
Calcula o ticket médio dividindo o total pela quantidade de documentos.

### Exemplo 5: Múltiplos Filtros
```
CALCULATE(SUM([TotalLinha]);[Ano]=2025;[Mes]=12)
```
Soma o total apenas para dezembro de 2025.

## ⚠️ Observações Importantes

1. **Nomes de Campos**: Os nomes dos campos devem corresponder exatamente aos nomes retornados pela query do relatório.

2. **Aspas nos Filtros**: Use aspas simples (`'`) ou duplas (`"`) para valores de texto nos filtros.

3. **Case Sensitivity**: Os nomes de campos são case-sensitive. Use exatamente como aparecem no relatório.

4. **Operadores Matemáticos**: 
   - `+` : Soma
   - `-` : Subtração
   - `*` : Multiplicação
   - `/` : Divisão
   - `()` : Parênteses para agrupar

5. **Decimais**: Use ponto (`.`) como separador decimal, não vírgula.

6. **CALCULATE Aninhado**: O sistema processa CALCULATE do mais interno para o mais externo automaticamente.

## 🚀 Como Usar

1. Acesse **Criar Dashboard** ou edite um dashboard existente
2. Na seção **"Medidas Calculadas"**, clique em **"Nova Medida"**
3. Digite o nome da medida (ex: "% Desconto")
4. Cole a fórmula no formato Power BI/DAX
5. Selecione o formato de exibição (number, currency, percent)
6. A medida aparecerá no painel de campos disponíveis
7. Use a medida em KPIs, gráficos ou outras fórmulas

## 📊 Exemplo Completo: Fórmula do Power BI

**Fórmula Original (Power BI):**
```
% Desconto = CALCULATE([Total Desconto]/ CALCULATE(SUM(fRM_VENDAS[Total s/ Desc]);fRM_VENDAS[Tipo Saida]="Venda"))
```

**Como usar no Dashboard:**
1. Nome da Medida: `% Desconto`
2. Fórmula: `CALCULATE([Total Desconto]/ CALCULATE(SUM(fRM_VENDAS[Total s/ Desc]);fRM_VENDAS[Tipo Saida]="Venda"))`
3. Formato: `percent`

**Nota**: Certifique-se de que os campos `Total Desconto`, `Total s/ Desc` e `Tipo Saida` existem no relatório selecionado.

