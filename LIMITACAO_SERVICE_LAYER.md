# ⚠️ LIMITAÇÃO IMPORTANTE - SAP SERVICE LAYER

## 🚫 **PROBLEMA IDENTIFICADO:**

A **SAP Service Layer** (REST API) **NÃO SUPORTA** queries SQL arbitrárias diretamente!

Ela usa **OData** e requer endpoints específicos.

---

## 📊 **O QUE A SERVICE LAYER SUPORTA:**

### ✅ **Endpoints OData (Funcionam):**

1. **Itens (`/Items`):**
   ```
   GET /b1s/v1/Items?$select=ItemCode,ItemName,OnHand&$filter=OnHand gt 0&$top=20
   ```

2. **Parceiros de Negócio (`/BusinessPartners`):**
   ```
   GET /b1s/v1/BusinessPartners?$select=CardCode,CardName&$filter=CardType eq 'C'&$top=20
   ```

3. **Notas Fiscais (`/Invoices`):**
   ```
   GET /b1s/v1/Invoices?$select=DocNum,DocDate,DocTotal&$top=20
   ```

### ❌ **Não Suporta:**
- Queries SQL livres (SELECT * FROM OITM)
- JOINs complexos
- Queries personalizadas

---

## 🔧 **SOLUÇÕES DISPONÍVEIS:**

### **OPÇÃO 1: Usar ODBC/HDBODBC** ⭐ **RECOMENDADO para queries SQL**

**Vantagem:**
- ✅ Suporta qualquer query SQL
- ✅ Acesso direto ao banco HANA
- ✅ Máximo controle

**Desvantagem:**
- ❌ Precisa instalar driver HDBODBC
- ❌ Apenas Windows/Linux

**Como instalar:**
1. Baixar SAP HANA Client: https://tools.hana.ondemand.com/#hanatools
2. Instalar no servidor web
3. Configurar DSN

---

### **OPÇÃO 2: Adaptar para OData** ⭐ **SEM instalar driver**

**Vantagem:**
- ✅ Não precisa instalar nada
- ✅ Funciona via REST

**Desvantagem:**
- ❌ Limitado aos endpoints SAP
- ❌ Não suporta queries complexas

**Exemplo de adaptação:**

**Query SQL Original:**
```sql
SELECT TOP 20 "ItemCode", "ItemName", "OnHand"
FROM OITM
WHERE "OnHand" > 0
ORDER BY "OnHand" DESC
```

**Equivalente OData:**
```
/Items?$select=ItemCode,ItemName,OnHand&$filter=OnHand gt 0&$orderby=OnHand desc&$top=20
```

---

## 🎯 **RECOMENDAÇÃO:**

Para usar **queries SQL livres** no SAP B1, você **PRECISA** de uma destas opções:

1. ✅ **Instalar driver HDBODBC** (melhor para SQL)
2. ✅ **Converter queries para OData** (sem instalar nada)

---

## 📋 **COMO PROCEDER:**

### **Se você pode instalar software no servidor:**
**→ Use ODBC (opção 1)**

1. Baixar SAP HANA Client
2. Instalar
3. Usar `SapB1HanaConnection`

### **Se NÃO pode instalar:**
**→ Use OData (opção 2)**

1. Converter queries para formato OData
2. Usar endpoints específicos
3. Implementar tradutor SQL → OData

---

## 💡 **QUAL VOCÊ PREFERE?**

**Opção A:** Instalar HDBODBC (queries SQL completas)
**Opção B:** Continuar com Service Layer (apenas endpoints OData)

