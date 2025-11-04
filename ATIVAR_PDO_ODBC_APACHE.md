# 🔧 ATIVAR PDO_ODBC NO APACHE

## 🎯 **PROBLEMA:**
- ✅ PHP CLI (terminal): pdo_odbc funciona
- ❌ PHP Apache (navegador): pdo_odbc NÃO funciona

**Causa:** 2 php.ini diferentes!

---

## 📋 **SOLUÇÃO (3 PASSOS):**

### **PASSO 1: Descobrir qual php.ini o Apache usa**

Acesse no navegador:
```
http://192.168.3.38/administrativo/test_php_info.php
```

Procure: **"Loaded Configuration File"**

Anote o caminho que aparece (exemplo):
```
C:\wamp64\bin\apache\apache2.4.x\bin\php.ini
OU
C:\wamp64\bin\php\php8.3.14\phpForApache.ini
OU
C:\wamp64\bin\php\php8.3.14\php.ini (mesmo do CLI)
```

---

### **PASSO 2: Editar o php.ini do Apache**

Abra o arquivo que encontrou no passo 1.

**Procure por:** `pdo_odbc` (Ctrl+F)

**Opção A:** Se encontrar `;extension=pdo_odbc`
```ini
;extension=pdo_odbc  ← ANTES (comentado)
extension=pdo_odbc   ← DEPOIS (ativo)
```

**Opção B:** Se não encontrar, adicione na seção `[Extensions]`:
```ini
[Extensions]
extension=pdo_odbc  ← ADICIONE ESTA LINHA
```

**Salve** (Ctrl+S)

---

### **PASSO 3: Reiniciar Apache**

**Opção A: Via ícone WAMP**
```
Ícone WAMP (barra de tarefas)
→ Restart All Services
```

**Opção B: Via linha de comando**
```cmd
net stop wampapache64
net start wampapache64
```

---

### **PASSO 4: Verificar**

Recarregue:
```
http://192.168.3.38/administrativo/test_php_info.php
```

Procure por: **"pdo_odbc"**

Deve aparecer:
```
PDO drivers: mysql, odbc, sqlite  ← ODBC deve estar aqui!
```

---

## ✅ **DEPOIS:**

Teste no sistema:
```
http://192.168.3.38/administrativo/dynamic-report-builder
```

Query SAP:
```sql
SELECT * FROM OITM
```

**DEVE FUNCIONAR!** 🎉

