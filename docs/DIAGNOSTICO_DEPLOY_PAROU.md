# 🔍 Diagnóstico: Deploy Parou de Funcionar

## 📊 **Possíveis Causas**

### **1. Secrets do GitHub Expirados/Incorretos**
- ✅ **FTP_SERVER_PROD** pode ter mudado
- ✅ **FTP_USER_PROD** ou **FTP_PASS_PROD** podem estar incorretos
- ✅ Secrets podem ter sido removidos ou expirados

**Como verificar:**
1. Acesse: `https://github.com/mendes31/administrativo/settings/secrets/actions`
2. Verifique se os secrets `FTP_SERVER_PROD`, `FTP_USER_PROD`, `FTP_PASS_PROD` existem
3. Verifique se os valores estão corretos

---

### **2. Action FTP-Deploy-Action Descontinuada/Atualizada**
- ✅ A versão `v4.3.4` pode ter sido descontinuada
- ✅ GitHub Actions pode ter mudado políticas de segurança
- ✅ A action pode ter sido movida ou renomeada

**Como verificar:**
1. Acesse: `https://github.com/SamKirkland/FTP-Deploy-Action`
2. Verifique se a versão `v4.3.4` ainda existe
3. Verifique se há versões mais recentes disponíveis

---

### **3. Servidor FTP Bloqueando GitHub Actions**
- ✅ IP do GitHub Actions pode estar bloqueado
- ✅ Firewall do servidor pode estar bloqueando conexões
- ✅ Rate limiting pode estar ativo

**Como verificar:**
1. Verifique logs do servidor FTP
2. Verifique firewall/whitelist do servidor
3. Tente conectar manualmente via FileZilla

---

### **4. Timeout ou Limite de Execução**
- ✅ Workflow pode estar excedendo tempo máximo
- ✅ Muitos arquivos podem estar causando timeout
- ✅ Conexão FTP pode estar lenta

**Como verificar:**
1. Acesse: `https://github.com/mendes31/administrativo/actions`
2. Clique no último workflow que falhou
3. Verifique se há mensagens de timeout

---

### **5. Permissões no Servidor**
- ✅ Usuário FTP pode ter perdido permissões de escrita
- ✅ Diretórios podem ter permissões incorretas
- ✅ Quota de disco pode estar cheia

**Como verificar:**
1. Conecte via SSH ao servidor
2. Verifique permissões: `ls -la`
3. Verifique espaço em disco: `df -h`

---

## ✅ **Soluções Rápidas**

### **1. Atualizar Versão da Action**
```yaml
uses: SamKirkland/FTP-Deploy-Action@v4.3.4
```
Tente atualizar para a versão mais recente:
```yaml
uses: SamKirkland/FTP-Deploy-Action@v4.3.4
# ou
uses: SamKirkland/FTP-Deploy-Action@master
```

### **2. Verificar Secrets**
1. Acesse: `https://github.com/mendes31/administrativo/settings/secrets/actions`
2. Verifique se todos os secrets estão configurados
3. Atualize se necessário

### **3. Testar Conexão Manual**
Use FileZilla ou outro cliente FTP para testar:
- Servidor: valor de `FTP_SERVER_PROD`
- Usuário: valor de `FTP_USER_PROD`
- Senha: valor de `FTP_PASS_PROD`

### **4. Verificar Logs do GitHub Actions**
1. Acesse: `https://github.com/mendes31/administrativo/actions`
2. Clique no último workflow que falhou
3. Expanda os steps para ver erros específicos
4. Procure por mensagens como:
   - "Connection refused"
   - "Authentication failed"
   - "Timeout"
   - "Permission denied"

---

## 🔧 **Próximos Passos**

1. **Verificar logs do GitHub Actions** para identificar o erro específico
2. **Testar conexão FTP manual** para confirmar credenciais
3. **Verificar secrets do GitHub** para garantir que estão corretos
4. **Atualizar versão da action** se necessário
5. **Verificar permissões no servidor** via SSH

---

## 📞 **Informações Necessárias**

Para diagnosticar melhor, preciso de:
1. **Mensagem de erro completa** do GitHub Actions
2. **Último workflow que funcionou** (data/hora)
3. **Logs do step que falhou** (copiar e colar)
4. **Status da conexão FTP manual** (funciona ou não?)

