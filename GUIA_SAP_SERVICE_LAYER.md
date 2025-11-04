# 📘 GUIA COMPLETO - SAP SERVICE LAYER

## 🎯 **O QUE É SERVICE LAYER?**

A **Service Layer do SAP Business One** é uma **API RESTful** que permite:
- ✅ Conexão via HTTP/HTTPS (sem ODBC)
- ✅ Queries SQL personalizadas
- ✅ Acesso a entidades (Items, BusinessPartners, etc)
- ✅ Operações CRUD (Create, Read, Update, Delete)
- ✅ Filtros OData
- ✅ Autenticação por sessão (cookies)

---

## ⚙️ **CONFIGURAÇÃO NO .ENV:**

Adicione ao seu `.env`:

```env
# SAP Business One - Service Layer
SAP_SL_URL=https://192.168.1.100:50000/b1s/v1
SAP_SL_USERNAME=manager
SAP_SL_PASSWORD=sua_senha
SAP_SL_COMPANY=SBODEMOUS
```

**Parâmetros:**
- `SAP_SL_URL`: URL base da Service Layer (porta padrão: 50000)
- `SAP_SL_USERNAME`: Usuário do SAP B1
- `SAP_SL_PASSWORD`: Senha do usuário
- `SAP_SL_COMPANY`: Nome do banco de dados da empresa

---

## 🚀 **COMO USAR:**

### **1. Executar Query SQL Personalizada:**

```php
use App\adms\Models\Services\SapB1ServiceLayer;

$sap = new SapB1ServiceLayer();

// Login automático na primeira chamada
$result = $sap->executeQuery("
    SELECT TOP 10 
        \"ItemCode\", 
        \"ItemName\", 
        \"OnHand\" 
    FROM OITM 
    WHERE \"OnHand\" > 0
");

if ($result['success']) {
    foreach ($result['data'] as $item) {
        echo $item['ItemCode'] . ": " . $item['ItemName'] . "\n";
    }
}
```

---

### **2. Buscar Itens (OData):**

```php
$sap = new SapB1ServiceLayer();

$items = $sap->getItems([
    'select' => 'ItemCode,ItemName,OnHand',
    'filter' => 'OnHand gt 100'
], 20); // Top 20

if ($items['success']) {
    print_r($items['data']);
}
```

---

### **3. Buscar Clientes:**

```php
$sap = new SapB1ServiceLayer();

$clientes = $sap->getBusinessPartners([
    'select' => 'CardCode,CardName,Phone1',
    'filter' => 'CardType eq \'C\''
], 50);

if ($clientes['success']) {
    foreach ($clientes['data'] as $cliente) {
        echo $cliente['CardName'] . "\n";
    }
}
```

---

### **4. Queries Complexas:**

```php
$sap = new SapB1ServiceLayer();

// Notas fiscais do mês atual
$result = $sap->executeQuery("
    SELECT 
        \"DocNum\",
        \"DocDate\",
        \"CardName\",
        \"DocTotal\"
    FROM OINV
    WHERE MONTH(\"DocDate\") = MONTH(CURRENT_DATE)
    AND YEAR(\"DocDate\") = YEAR(CURRENT_DATE)
    ORDER BY \"DocDate\" DESC
");
```

---

## 📊 **FILTROS ODATA:**

### **Operadores:**
- `eq` - Igual (=)
- `ne` - Diferente (!=)
- `gt` - Maior que (>)
- `lt` - Menor que (<)
- `ge` - Maior ou igual (>=)
- `le` - Menor ou igual (<=)
- `and` - E lógico
- `or` - Ou lógico

### **Exemplos:**

```php
// Itens com estoque > 100
'filter' => 'OnHand gt 100'

// Clientes do tipo 'C' (Customer)
'filter' => 'CardType eq \'C\''

// Itens ativos com estoque
'filter' => 'validFor eq \'Y\' and OnHand gt 0'

// Clientes com saldo > 1000
'filter' => 'Balance gt 1000'
```

---

## 🔧 **INTEGRAÇÃO COM RELATÓRIOS DINÂMICOS:**

### **Atualizar DynamicQueryBuilderService:**

```php
use App\adms\Models\Services\SapB1ServiceLayer;

class DynamicQueryBuilderService
{
    private function executeReport(array $config): array
    {
        if ($config['query_mode'] === 'custom_sql') {
            $sql = $config['custom_sql'];
            
            // Detectar se é SAP B1
            if ($this->detectConnectionFromSQL($sql) === 'sap_b1') {
                $sap = new SapB1ServiceLayer();
                return $sap->executeQuery($sql);
            }
            
            // Senão, executar local
            return $this->executeCustomSQL($config);
        }
        
        // Builder mode...
    }
}
```

---

## 🧪 **TESTE DE CONEXÃO:**

Execute o script de teste:

```bash
php scripts/test_sap_service_layer.php
```

**Resultado esperado:**
```
🧪 TESTE DE CONEXÃO - SAP SERVICE LAYER
═══════════════════════════════════════════════════════════════

📊 Teste 1: Login
───────────────────────────────────────────────────────────────
✅ Login realizado com sucesso!

📊 Teste 2: Query SQL (Primeiros 5 itens)
───────────────────────────────────────────────────────────────
✅ Query executada com sucesso!
  Registros retornados: 5

  Dados:
    - A00001: Item de Teste (Estoque: 100)
    - A00002: Outro Item (Estoque: 50)
    ...
```

---

## 📚 **TABELAS PRINCIPAIS SAP B1:**

### **Cadastros:**
- `OITM` - Itens
- `OCRD` - Parceiros de Negócio
- `OITW` - Estoque por Depósito
- `OHEM` - Funcionários
- `OWHS` - Depósitos

### **Vendas:**
- `OINV` - Notas Fiscais de Saída
- `ORDR` - Pedidos de Venda
- `OQUT` - Cotações de Venda
- `RIN1` - Linhas de NF Saída

### **Compras:**
- `OPCH` - Notas Fiscais de Entrada
- `OPOR` - Pedidos de Compra
- `PCH1` - Linhas de NF Entrada

### **Financeiro:**
- `ORCT` - Contas a Receber
- `OVPM` - Contas a Pagar
- `JDT1` - Lançamentos Contábeis

---

## 💡 **VANTAGENS DA SERVICE LAYER:**

### **vs ODBC/HDBODBC:**
✅ Não precisa instalar driver ODBC
✅ Funciona em qualquer SO (Windows, Linux, Mac)
✅ Conexão via HTTP (sem porta HANA 30015)
✅ Autenticação mais segura (sessões)
✅ Suporte a filtros OData
✅ Acesso a entidades completas (não só queries)

### **vs Acesso Direto HANA:**
✅ Usa credenciais do SAP B1 (não do HANA)
✅ Respeita permissões do SAP B1
✅ Não expõe estrutura do banco diretamente
✅ API oficial e suportada

---

## 🔒 **SEGURANÇA:**

### **Boas Práticas:**
1. ✅ Use HTTPS na URL (não HTTP)
2. ✅ Armazene credenciais no `.env` (nunca no código)
3. ✅ Valide queries SQL antes de executar
4. ✅ Use `$top` para limitar resultados
5. ✅ Faça logout após uso (automático no destrutor)

### **Validação de Query:**
```php
// Apenas SELECT
if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
    throw new Exception('Apenas queries SELECT são permitidas');
}

// Limitar resultados
if (!stripos($sql, 'TOP')) {
    $sql = preg_replace('/^SELECT\s+/i', 'SELECT TOP 1000 ', $sql);
}
```

---

## 📖 **DOCUMENTAÇÃO OFICIAL:**

**SAP Service Layer Guide:**
https://help.sap.com/doc/0d2533e7e54a44d7a2e28f0c196d4a9e/10.0/en-US/index.html

**OData Query Options:**
https://www.odata.org/documentation/

---

## 🎯 **PRÓXIMOS PASSOS:**

1. ✅ Adicionar credenciais no `.env`
2. ✅ Executar `php scripts/test_sap_service_layer.php`
3. ✅ Se funcionar, integrar ao QueryBuilder
4. ✅ Testar queries no Construtor de Relatórios
5. ✅ Criar relatórios SAP B1!

---

**Quer que eu integre ao sistema de relatórios agora?** 🚀

