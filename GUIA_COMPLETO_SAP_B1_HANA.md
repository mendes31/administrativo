# 🔷 GUIA COMPLETO: PHP + SAP BUSINESS ONE HANA

## 📋 CONSULTAS MAIS USADAS

### 1. Clientes Ativos
```php
$clientes = SapB1HanaConnection::query(
    "SELECT CardCode, CardName, Phone1, E_Mail, Balance, CreditLine
     FROM OCRD
     WHERE CardType = 'C' AND frozenFor = 'N'
     ORDER BY CardName"
);
```

### 2. Notas Fiscais do Mês
```php
$sql = "SELECT DocNum, DocDate, CardName, DocTotal, DocStatus
        FROM OINV
        WHERE YEAR(DocDate) = YEAR(CURRENT_DATE)
          AND MONTH(DocDate) = MONTH(CURRENT_DATE)
        ORDER BY DocDate DESC";
$notas = SapB1HanaConnection::query($sql);
```

### 3. Top 10 Produtos Mais Vendidos
```php
$sql = "SELECT T1.ItemCode, T2.ItemName, SUM(T1.Quantity) as qtd, SUM(T1.LineTotal) as valor
        FROM INV1 T1
        INNER JOIN OITM T2 ON T1.ItemCode = T2.ItemCode
        INNER JOIN OINV T0 ON T0.DocEntry = T1.DocEntry
        WHERE YEAR(T0.DocDate) = YEAR(CURRENT_DATE)
        GROUP BY T1.ItemCode, T2.ItemName
        ORDER BY qtd DESC
        LIMIT 10";
$topProdutos = SapB1HanaConnection::query($sql);
```

### 4. Saldo de Estoque
```php
$sql = "SELECT T0.ItemCode, T1.ItemName, T0.WhsCode, T2.WhsName, T0.OnHand, T1.AvgPrice
        FROM OITW T0
        INNER JOIN OITM T1 ON T0.ItemCode = T1.ItemCode
        INNER JOIN OWHS T2 ON T0.WhsCode = T2.WhsCode
        WHERE T0.OnHand > 0
        ORDER BY T1.ItemName";
$estoque = SapB1HanaConnection::query($sql);
```

### 5. Pedidos Abertos
```php
$pedidos = SapB1HanaConnection::query(
    "SELECT DocNum, DocDate, CardName, DocTotal
     FROM ORDR
     WHERE DocStatus = 'O'
     ORDER BY DocDate DESC"
);
```

### 6. Títulos a Receber Vencidos
```php
$sql = "SELECT DocNum, DocDueDate, CardName, (DocTotal - PaidToDate) as saldo
        FROM OINV
        WHERE DocStatus = 'O' 
          AND DocDueDate < CURRENT_DATE
          AND (DocTotal - PaidToDate) > 0
        ORDER BY DocDueDate";
$vencidos = SapB1HanaConnection::query($sql);
```

### 7. Faturamento por Vendedor
```php
$sql = "SELECT T1.SlpCode, T1.SlpName, COUNT(T0.DocEntry) as vendas, SUM(T0.DocTotal) as valor
        FROM OINV T0
        INNER JOIN OSLP T1 ON T0.SlpCode = T1.SlpCode
        WHERE MONTH(T0.DocDate) = MONTH(CURRENT_DATE)
        GROUP BY T1.SlpCode, T1.SlpName
        ORDER BY valor DESC";
$vendedores = SapB1HanaConnection::query($sql);
```

---

## 📊 PRINCIPAIS TABELAS

### Vendas
- **OINV** - Notas Fiscais Saída
- **ORDR** - Pedidos de Venda
- **OQUT** - Cotações
- **ODLN** - Entregas
- **INV1**, **RDR1**, **QUT1**, **DLN1** - Linhas dos documentos

### Compras
- **OPCH** - Notas Fiscais Entrada
- **OPOR** - Pedidos de Compra
- **OPQT** - Cotações de Compra
- **PCH1**, **POR1**, **PQT1** - Linhas dos documentos

### Cadastros
- **OCRD** - Clientes e Fornecedores
- **OITM** - Itens (Produtos)
- **OWHS** - Depósitos
- **OSLP** - Vendedores
- **OHEM** - Funcionários

### Financeiro
- **ORCT** - Recebimentos
- **OVPM** - Pagamentos
- **JDT1** - Lançamentos Contábeis
- **OACT** - Plano de Contas

---

## 🎯 CAMPOS MAIS USADOS

### OCRD (Clientes/Fornecedores)
- `CardCode` - Código
- `CardName` - Nome
- `CardType` - Tipo ('C'=Cliente, 'S'=Fornecedor)
- `Phone1`, `Phone2` - Telefones
- `E_Mail` - E-mail
- `Balance` - Saldo
- `CreditLine` - Limite de Crédito
- `frozenFor` - Bloqueado? ('N'=Não, 'Y'=Sim)

### OINV (Notas Fiscais Saída)
- `DocEntry` - Chave interna
- `DocNum` - Número da NF
- `DocDate` - Data de emissão
- `DocDueDate` - Data de vencimento
- `CardCode` - Código do cliente
- `CardName` - Nome do cliente
- `DocTotal` - Valor total
- `PaidToDate` - Valor pago
- `DocStatus` - Status ('O'=Aberto, 'C'=Fechado)

### OITM (Itens)
- `ItemCode` - Código do item
- `ItemName` - Descrição
- `OnHand` - Estoque atual
- `AvgPrice` - Custo médio
- `LastPurPrc` - Última compra

---

## 💡 DICAS

### Query com Parâmetros
```php
$cliente = SapB1HanaConnection::queryFirst(
    "SELECT * FROM OCRD WHERE CardCode = ?",
    ['C00001']
);
```

### Performance
```php
// ❌ LENTO - sem índice
SELECT * FROM OINV WHERE CardName LIKE '%João%'

// ✅ RÁPIDO - com índice
SELECT * FROM OINV WHERE CardCode = 'C00001'
```

### Joins Eficientes
```php
// Sempre faça JOIN em campos indexados (DocEntry, CardCode, ItemCode)
SELECT T0.DocNum, T0.CardName, T1.ItemCode, T1.Quantity
FROM OINV T0
INNER JOIN INV1 T1 ON T0.DocEntry = T1.DocEntry
WHERE T0.CardCode = 'C00001'
```

---

## ⚠️ CUIDADOS

1. **Não modificar dados** - SAP B1 tem triggers complexos
2. **Usar transações** - Para múltiplas queries
3. **Cuidado com performance** - Evitar SELECT * em tabelas grandes
4. **Sempre filtrar** - Use WHERE para limitar resultados

---

✅ **Pronto para usar!** 🎉

