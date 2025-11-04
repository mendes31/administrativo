# ⚡ CONFIGURAÇÃO RÁPIDA SAP B1 + HANA

## 1️⃣ ADICIONAR NO .env

```env
# SAP Business One - HANA
SAP_B1_DSN=SAP_B1_HANA
SAP_B1_HOST=172.16.0.100
SAP_B1_PORT=30015
SAP_B1_DATABASE=SBODEMOUSA
SAP_B1_USER=SYSTEM
SAP_B1_PASSWORD=SuaSenha
SAP_B1_TIMEOUT=30
```

**⚠️ Substitua pelos SEUS dados reais!**

---

## 2️⃣ CONFIGURAR DSN

### Windows:
1. Abrir: `odbcad32.exe` (ou SysWOW64\odbcad32.exe para 32-bit)
2. System DSN → Add → HDBODBC
3. Configurar:
   - Name: `SAP_B1_HANA`
   - Server: `172.16.0.100:30015`
   - Database: `SBODEMOUSA`
   - Username: `SYSTEM`
   - Password: `sua_senha`
4. Test Connection → OK

### Linux:
```bash
sudo nano /etc/odbc.ini
```

Adicionar:
```ini
[SAP_B1_HANA]
Driver = HDBODBC
ServerNode = 172.16.0.100:30015
Database = SBODEMOUSA
User = SYSTEM
Password = SuaSenha
```

---

## 3️⃣ TESTAR CONEXÃO

```bash
cd C:\wamp64\www\administrativo
php scripts/test_sap_b1_connection.php
```

**Resultado esperado:**
```
✅ CONEXÃO ESTABELECIDA COM SUCESSO!
✅ Total de Clientes: 1,234
✅ Total de Itens: 8,901
```

---

## 4️⃣ USAR NO CÓDIGO

```php
<?php
use App\adms\Models\Services\SapB1HanaConnection;

// Buscar clientes
$clientes = SapB1HanaConnection::query(
    "SELECT CardCode, CardName FROM OCRD WHERE CardType = 'C'"
);

// Buscar notas fiscais
$notas = SapB1HanaConnection::query(
    "SELECT DocNum, DocDate, DocTotal FROM OINV 
     WHERE MONTH(DocDate) = MONTH(CURRENT_DATE)"
);
```

---

## 📊 PRINCIPAIS TABELAS SAP B1

| Tabela | Descrição |
|--------|-----------|
| `OCRD` | Clientes e Fornecedores |
| `OINV` | Notas Fiscais de Saída |
| `ORDR` | Pedidos de Venda |
| `OITM` | Itens (Produtos) |
| `OITW` | Saldo de Estoque |
| `ORCT` | Recebimentos |
| `OVPM` | Pagamentos |

---

## ✅ PRONTO!

Agora você pode consultar dados do SAP B1 em tempo real! 🎉

