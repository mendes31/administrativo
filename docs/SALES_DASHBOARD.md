# 📊 Dashboard de Vendas SAP B1

Dashboard interativo para análise de vendas do SAP Business One com filtros dinâmicos e KPIs em tempo real.

## ✨ Funcionalidades

### 🎯 KPIs Principais
- **Faturamento Total**: Soma de todas as vendas
- **Ticket Médio**: Valor médio por venda
- **Quantidade de Vendas**: Total de documentos
- **Itens Vendidos**: Quantidade total de produtos
- **Custo + Impostos**: Custo total com impostos
- **Desconto Total**: Valor total de descontos
- **% Desconto**: Percentual de desconto sobre faturamento

### 🔍 Filtros Disponíveis
1. **Ano** (obrigatório) - Últimos 5 anos
2. **Mês** (opcional) - Janeiro a Dezembro
3. **Grupo de Parceiro** (opcional) - Carregado dinamicamente
4. **Vendedor** (opcional) - Carregado dinamicamente

### 📈 Visualizações
- **Gráfico de Barras**: Faturamento mensal
- **Tabela Detalhada**: Vendas por vendedor com:
  - Faturamento
  - Quantidade de vendas
  - Ticket médio
  - Itens vendidos

## 🚀 Como Usar

### 1. Acessar o Dashboard
Navegue até: `Relatórios > Dashboard de Vendas SAP B1`

### 2. Selecionar Filtros
- Escolha o **Ano** desejado
- Opcionalmente, selecione **Mês**, **Grupo** ou **Vendedor**
- Clique no botão **"Consultar"**

### 3. Analisar Dados
- Os KPIs são atualizados automaticamente
- O gráfico mostra a tendência mensal
- A tabela detalha por vendedor

## 📝 Query SQL Base

```sql
SELECT
    YEAR(T0."DocDate") AS "Ano",
    MONTH(T0."DocDate") AS "Mes",
    T0."DocNum" AS "NumDoc",
    T0."DocDate" AS "DataCriacao",
    T0."CardName" AS "nomePN",
    T7."GroupName" AS "nomeGrupoPN",
    T2."SlpName" AS "nomeVendedor",
    T1."ItemCode" AS "cdItem",
    T1."Quantity" AS "Qtde",
    T1."LineTotal" AS "TotalLinha",
    -- ... outros campos
FROM OINV T0
INNER JOIN INV1 T1 ON T0."DocEntry" = T1."DocEntry"
INNER JOIN OSLP T2 ON T0."SlpCode" = T2."SlpCode"
-- ... outros joins
WHERE T0."DocType" = 'I'
AND T0."CANCELED" = 'N'
AND YEAR(T0."DocDate") = {ano_selecionado}
-- ... filtros adicionais
LIMIT 5000
```

## ⚙️ Configuração Técnica

### Requisitos
- PHP 8.0+
- Extensão PDO ODBC (para SAP HANA)
- Memória PHP: 512MB (configurado automaticamente)
- Timeout: 180 segundos

### Limites de Segurança
- **Máximo de registros**: 5.000 por consulta
- **LIMIT automático**: 5.000 se não especificado
- **Processamento**: Por chunks (economia de memória)

### Arquivos Principais
```
app/adms/Controllers/reports/
├── SalesDashboard.php          # Controller principal
└── SalesDashboardData.php      # API de dados

app/adms/Views/reports/
└── sales-dashboard.php         # Interface do dashboard
```

## 🔒 Permissões

Para acessar o dashboard, execute a migration:

```bash
mysql -u root -p administrativo < database/migrations/add_sales_dashboard_pages.sql
```

Depois, configure as permissões em:
`Configurações > Níveis de Acesso > [Seu Nível] > Páginas`

## 💡 Dicas de Uso

### Para Melhor Performance
1. Use filtros de **Mês** para reduzir volume de dados
2. Combine **Ano + Mês** para análises detalhadas
3. Use **Vendedor** para análise individual

### Para Relatórios Específicos
- **Vendas do Trimestre**: Filtre por ano e refine manualmente
- **Performance de Vendedor**: Use filtro de vendedor específico
- **Análise de Grupo**: Filtre por grupo de parceiro

### Exportação
Para exportar dados:
1. Execute a consulta desejada
2. Use o Dynamic Report Builder para criar um relatório salvo
3. Ou copie os dados diretamente da tabela

## 🐛 Troubleshooting

### Erro de Memória
**Sintoma**: "Allowed memory size exhausted"
**Solução**: 
- Adicione mais filtros (mês, vendedor)
- O sistema já está configurado com 512MB
- Use períodos menores

### Consulta Lenta
**Sintoma**: Demora mais de 30 segundos
**Solução**:
- Reduza o período (use filtro de mês)
- Verifique conexão com SAP HANA
- Limite já está em 5.000 registros

### Dados Não Aparecem
**Sintoma**: Dashboard vazio após consulta
**Solução**:
- Verifique se há vendas no período selecionado
- Teste com ano anterior
- Verifique log do PHP para erros de conexão

## 📞 Suporte

Em caso de dúvidas ou problemas, verifique:
1. Logs do PHP: `C:\wamp64\logs\php_error.log`
2. Console do navegador (F12)
3. Teste a conexão SAP B1 HANA

## 🔄 Atualizações Futuras

Planejado:
- [ ] Exportação para Excel
- [ ] Comparação entre períodos
- [ ] Alertas de meta
- [ ] Dashboard mobile
- [ ] Filtros salvos

