# 📘 INSTALAÇÃO HDBODBC - GUIA COMPLETO

## 🎯 **OBJETIVO:**
Instalar driver HDBODBC para conectar PHP ao SAP HANA (SAP B1).

---

## 📋 **CHECKLIST DE INSTALAÇÃO:**

- [ ] 1. Download do SAP HANA Client
- [ ] 2. Extrair o instalador
- [ ] 3. Instalar o HANA Client
- [ ] 4. Configurar DSN no Windows
- [ ] 5. Ativar extensão pdo_odbc no PHP
- [ ] 6. Testar conexão
- [ ] 7. Atualizar código do sistema

---

## 🔧 **DETALHES DE CADA PASSO:**

### **1. DOWNLOAD (5 min)**

**URL:** https://tools.hana.ondemand.com/#hanatools

**Arquivo:** SAP HANA Client 2.x (Windows 64-bit)

**Tamanho:** ~250 MB

---

### **2. EXTRAIR (2 min)**

**Ferramenta:** SAPCAR ou 7-Zip

**Comando SAPCAR:**
```cmd
SAPCAR.exe -xvf IMDB_CLIENT20_xxx.SAR
```

**OU** 7-Zip: Botão direito → Extract

---

### **3. INSTALAR (5 min)**

**Executar:** `hdbsetup.exe` (como Admin)

**Pasta destino:** `C:\SAP\hdbclient`

**Componentes:** ✅ ODBC Driver

---

### **4. CONFIGURAR DSN (5 min)**

**Abrir:** `odbcad32.exe` (Win+R)

**System DSN → Add → HDBODBC**

**Configurações:**
```
DSN Name: SAP_B1_TIARAJU
Server: linux-7lxj:30015
Database: SBO_TIARAJU_HOM
User: SYSTEM (ou seu user)
Password: ******
```

**Testar:** Test Connection → ✅ Success

---

### **5. ATIVAR PDO_ODBC (2 min)**

**Editar:** `C:\wamp64\bin\php\phpX.X.X\php.ini`

**Adicionar/descomentar:**
```ini
extension=pdo_odbc
```

**Reiniciar:** Apache/PHP-FPM

**Verificar:**
```bash
php -m | findstr odbc
```

---

### **6. TESTAR (1 min)**

**Executar:**
```bash
cd C:\wamp64\www\administrativo
php scripts/test_hdbodbc_connection.php
```

**Resultado esperado:**
```
✅ PDO_ODBC: Ativa
✅ CONECTADO!
✅ Query OK
✅ 5 registros retornados
✅ HDBODBC FUNCIONANDO!
```

---

### **7. ATUALIZAR SISTEMA (2 min)**

**Editar `.env`:**
```env
# Adicionar configuração HANA
SAP_HANA_DSN=SAP_B1_TIARAJU
SAP_HANA_USERNAME=SYSTEM
SAP_HANA_PASSWORD=sua_senha
```

**Usar no código:**
```php
// SapB1HanaConnection já está pronto!
$hana = SapB1HanaConnection::getInstance();
$stmt = $hana->query("SELECT * FROM OITM");
```

---

## ⏱️ **TEMPO TOTAL:** ~20 minutos

---

## 🐛 **TROUBLESHOOTING:**

### **Erro: "Driver not found"**
```
Solução: Verificar se HDBODBC aparece em odbcad32.exe
```

### **Erro: "Could not connect"**
```
Solução:
1. Verificar porta HANA (30015)
2. Verificar firewall
3. Testar: telnet linux-7lxj 30015
```

### **Erro: "Extension not loaded"**
```
Solução:
1. Editar php.ini
2. Adicionar: extension=pdo_odbc
3. Reiniciar Apache
4. Verificar: php -m | findstr odbc
```

---

## 📞 **INFORMAÇÕES NECESSÁRIAS:**

Você precisa saber:

| Item | Valor para seu ambiente |
|------|------------------------|
| **Servidor HANA** | `linux-7lxj` |
| **Porta HANA** | `30015` (padrão) |
| **Banco de dados** | `SBO_TIARAJU_HOM` |
| **Usuário HANA** | `SYSTEM` ou outro |
| **Senha HANA** | (pergunte ao admin) |

---

## 🎯 **PRÓXIMOS PASSOS:**

1. ✅ Baixar SAP HANA Client
2. ✅ Seguir passos 2-6
3. ✅ Executar teste
4. ✅ Se funcionar = PRONTO!
5. ✅ Se erro = me mostrar a mensagem

---

**COMECE PELO DOWNLOAD!** 🚀

https://tools.hana.ondemand.com/#hanatools

