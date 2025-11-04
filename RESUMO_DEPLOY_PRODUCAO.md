# 🚀 Resumo: Deploy com SAP B1 Local

## 🎯 Seu Cenário

```
┌─────────────────────┐         Internet         ┌──────────────────┐
│  Servidor Web       │ ◄───────────────────────► │  Rede Empresa    │
│  (Hospedagem)       │                           │  (SAP B1 Local)  │
│                     │                           │                  │
│  - PHP              │         ❓ COMO?          │  - SAP HANA      │
│  - MySQL            │                           │  - IP: 192.168.X │
│  - Aplicação        │                           │                  │
└─────────────────────┘                           └──────────────────┘
```

---

## ✅ Solução Recomendada: **VPN**

```
┌─────────────────────┐                           ┌──────────────────┐
│  Servidor Web       │ ◄──── Túnel VPN ────────► │  Rede Empresa    │
│  (Hospedagem)       │      (Criptografado)      │  (SAP B1)        │
│                     │                           │                  │
│  Client VPN ────────┼───────────────────────────┼──► SAP HANA      │
│  Conecta em:        │                           │      Port 30015  │
│  192.168.X.X:30015  │                           │                  │
└─────────────────────┘                           └──────────────────┘
```

### ✨ Por que VPN?
✅ **Seguro** - Criptografia de ponta a ponta  
✅ **Estável** - Conexão permanente  
✅ **Profissional** - Padrão da indústria  
✅ **Flexível** - Acessa outros recursos da rede

---

## 📝 O Que Configurar

### **1. No Servidor de Hospedagem:**

#### **Instalar Cliente VPN:**
```bash
# Ubuntu/Debian
sudo apt-get install openvpn

# Conectar
sudo openvpn --config empresa.ovpn
```

#### **Arquivo `.env`:**
```env
# Usar IP interno via VPN
SAP_HANA_HOST=192.168.1.100
SAP_HANA_PORT=30015
SAP_HANA_USER=usuario_readonly
SAP_HANA_PASSWORD=senha_forte
SAP_HANA_SCHEMA=SBO_PRODUCAO
```

### **2. Na Empresa:**

#### **Configurar Servidor VPN:**
- Instalar OpenVPN Server
- Criar certificado para servidor web
- Liberar porta VPN no firewall (UDP 1194)

#### **Criar Usuário Read-Only no SAP:**
```sql
CREATE USER APP_READONLY PASSWORD "SenhaForte123!";
GRANT SELECT ON SCHEMA "SBO_PRODUCAO" TO APP_READONLY;
```

---

## 🔍 Testar Conectividade

Após configurar, execute:

```bash
php scripts/check_sap_connection.php
```

**Resultado Esperado:**
```
✅ Conexão estabelecida! (45ms)
✅ Query executada com sucesso!
✅ Acesso ao schema OK!
✅ Usuário é READ-ONLY
```

---

## ⚠️ NUNCA Fazer

❌ **Expor SAP HANA diretamente na internet**  
❌ **Usar usuário com permissões de escrita**  
❌ **Deixar senha no Git**  
❌ **Usar `APP_DEBUG=true` em produção**

---

## 📚 Documentação Completa

- **Guia Detalhado:** `DEPLOY_PRODUCAO_SAP_B1.md`
- **Exemplo de Config:** `config/env.production.example`
- **Script de Teste:** `scripts/check_sap_connection.php`

---

## 🆘 Problemas Comuns

### **"Could not find driver"**
➜ Instalar SAP HANA Client (ODBC) no servidor

### **"Connection refused"**
➜ Verificar se VPN está conectada  
➜ Testar: `ping 192.168.X.X`

### **"Access denied"**
➜ Verificar usuário/senha no `.env`  
➜ Verificar permissões no SAP HANA

### **"Table not found"**
➜ Verificar `SAP_HANA_SCHEMA`  
➜ Executar: `SET SCHEMA "SBO_PRODUCAO"`

---

## ✅ Checklist Rápido

```
[ ] VPN instalada e conectada
[ ] .env configurado com IP interno
[ ] Usuário read-only criado
[ ] Firewall liberado
[ ] HTTPS ativo
[ ] Debug desligado
[ ] Teste de conexão OK
```

---

**🎉 Pronto! Sua aplicação pode acessar o SAP B1 com segurança!**

