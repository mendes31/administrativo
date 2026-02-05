# 📏 Explicação: Tamanho de Coluna vs Índice Único

## 🔴 **Por que VARCHAR(255) não funciona com índice único?**

### **Cálculo de Bytes:**

- **VARCHAR(255)** com charset **utf8mb4**:
  - Cada caractere = **4 bytes**
  - 255 caracteres × 4 bytes = **1020 bytes**
  - Limite do MySQL para índices = **767 bytes**
  - **1020 > 767** ❌ **ERRO!**

### **Solução com VARCHAR(191):**

- **VARCHAR(191)** com charset **utf8mb4**:
  - 191 caracteres × 4 bytes = **764 bytes**
  - **764 < 767** ✅ **FUNCIONA!**

---

## ✅ **Opções Disponíveis**

### **Opção 1: Limitar Coluna para 191 (Recomendado)**

**Vantagens:**
- ✅ Índice único funciona
- ✅ Validação automática no banco
- ✅ 191 caracteres é suficiente para a maioria dos casos

**Desvantagens:**
- ⚠️ Limita o tamanho do campo

**SQL:**
```sql
ALTER TABLE `adms_access_levels` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);
```

---

### **Opção 2: Manter VARCHAR(255) SEM Índice Único**

**Vantagens:**
- ✅ Mantém 255 caracteres
- ✅ Sem limitação de tamanho

**Desvantagens:**
- ⚠️ Sem validação automática de unicidade no banco
- ⚠️ Deve garantir unicidade na aplicação (PHP)

**SQL:**
```sql
-- Remover índice único
ALTER TABLE `adms_access_levels` 
DROP INDEX IF EXISTS `idx_unique_name`;

-- Coluna permanece VARCHAR(255)
-- Unicidade deve ser validada no código PHP
```

---

### **Opção 3: Usar Prefix Key (Pode não funcionar)**

Alguns storage engines não suportam prefix keys em índices únicos:

```sql
-- TENTATIVA (pode dar erro #1089)
ALTER TABLE `adms_access_levels` 
ADD UNIQUE KEY `idx_unique_name` (`name`(191));
```

**Problema:** Se o storage engine for MyISAM ou versão antiga do MySQL, pode não funcionar.

---

## 🎯 **Recomendação**

**Para este projeto, use Opção 1 (VARCHAR(191)):**

1. **191 caracteres é suficiente** para nomes de:
   - Níveis de acesso
   - Departamentos
   - Formas de pagamento
   - Frequências
   - Centros de custo
   - Contas contábeis

2. **Validação automática** no banco é mais segura

3. **Melhor performance** com índice único

---

## 📋 **Exceções**

Algumas tabelas já têm colunas menores:
- `adms_payment_method.name` = VARCHAR(100) ✅ (não precisa limitar)
- Outras podem ter tamanhos diferentes

---

## 🔍 **Verificar Tamanho Atual da Coluna**

No phpMyAdmin, execute:

```sql
SHOW COLUMNS FROM `adms_access_levels` LIKE 'name';
```

Ou:

```sql
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'adms_access_levels' 
AND COLUMN_NAME = 'name';
```

---

## 💡 **Resumo**

| Tamanho | Bytes (utf8mb4) | Funciona com Índice Único? |
|---------|----------------|---------------------------|
| VARCHAR(191) | 764 bytes | ✅ SIM |
| VARCHAR(255) | 1020 bytes | ❌ NÃO |
| VARCHAR(255) sem índice | 1020 bytes | ✅ SIM (mas sem validação) |

**Conclusão:** Use **VARCHAR(191)** se precisar de índice único, ou **VARCHAR(255)** sem índice único se precisar de mais caracteres.

